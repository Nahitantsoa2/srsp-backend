<?php

namespace App\Services;

use App\Mail\DossierRejete;
use App\Models\Notification;
use App\Models\SuiviDossier;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Prévient les fonctionnaires lorsqu'un dossier du suivi est rejeté :
 * notification in-app + email, en rattachant le dossier à l'utilisateur
 * via le matricule (suivi_dossier.im == utilisateur.matricule).
 */
class RejetDossierNotifier
{
    /**
     * @param  iterable<SuiviDossier>  $dossiers  Dossiers à examiner.
     * @return int Nombre de fonctionnaires notifiés.
     */
    public function notifier(iterable $dossiers): int
    {
        $notifies = 0;

        foreach ($dossiers as $dossier) {
            if ($this->notifierDossier($dossier)) {
                $notifies++;
            }
        }

        return $notifies;
    }

    /**
     * Notifie (in-app + email) un fonctionnaire pour ses dossiers rejetés
     * non encore signalés. Appelé notamment à chaque connexion, afin que le
     * fonctionnaire soit averti même si le dossier a été importé après la
     * création de son compte.
     *
     * @return int Nombre de dossiers notifiés.
     */
    public function notifierPourFonctionnaire(Utilisateur $fonctionnaire): int
    {
        if ($fonctionnaire->role !== 'fonctionnaire') {
            return 0;
        }

        $im = trim((string) $fonctionnaire->matricule);

        if ($im === '') {
            return 0;
        }

        $notifies = 0;

        foreach (SuiviDossier::where('im', $im)->get() as $dossier) {
            if ($this->notifierDossier($dossier, $fonctionnaire)) {
                $notifies++;
            }
        }

        return $notifies;
    }

    /**
     * Notifie un seul dossier (si rejeté et rattachable à un fonctionnaire),
     * en évitant les doublons.
     */
    private function notifierDossier(SuiviDossier $dossier, ?Utilisateur $destinataire = null): bool
    {
        if (! $dossier->estRejete()) {
            return false;
        }

        $fonctionnaire = $destinataire ?? $this->fonctionnaireConcerne($dossier);

        if (! $fonctionnaire) {
            return false;
        }

        $message = $this->message($dossier);

        // La notification in-app est créée immédiatement. L'anti-doublon porte
        // sur le message, mais l'email est réessayé tant qu'il n'a pas réussi
        // (ex. import puis connexion, ou SMTP indisponible au premier essai).
        $notification = Notification::firstOrCreate(
            [
                'id_utilisateur' => $fonctionnaire->id_utilisateur,
                'message' => $message,
            ],
            [
                'lu' => false,
                'email_envoye' => false,
            ]
        );

        $action = $notification->wasRecentlyCreated;

        if (! $notification->email_envoye) {
            if ($this->envoyerEmail($dossier, $fonctionnaire)) {
                $notification->forceFill(['email_envoye' => true])->save();
                $action = true;
            }
        }

        return $action;
    }

    private function fonctionnaireConcerne(SuiviDossier $dossier): ?Utilisateur
    {
        if (trim((string) $dossier->im) === '') {
            return null;
        }

        return Utilisateur::where('role', 'fonctionnaire')
            ->where('matricule', $dossier->im)
            ->first();
    }

    private function message(SuiviDossier $dossier): string
    {
        $ref = $dossier->ref_dossier ?: ($dossier->ref_be ?: '—');
        $message = "❌ Votre dossier n° {$ref} a été rejeté.";

        if (trim((string) $dossier->motif_rejet) !== '') {
            $message .= " Motif : {$dossier->motif_rejet}.";
        }

        return $message;
    }

    private function envoyerEmail(SuiviDossier $dossier, Utilisateur $fonctionnaire): bool
    {
        $ref = $dossier->ref_dossier ?: ($dossier->ref_be ?: '—');

        try {
            Mail::to($fonctionnaire->email)->send(new DossierRejete($dossier, $fonctionnaire));

            return true;
        } catch (\Throwable $e) {
            // Un échec d'envoi d'email ne doit pas faire échouer l'import.
            // La notification garde email_envoye = false pour un nouvel essai.
            Log::error(
                "Échec de l'email de rejet (dossier {$ref}) pour {$fonctionnaire->email} : {$e->getMessage()}"
            );

            return false;
        }
    }
}
