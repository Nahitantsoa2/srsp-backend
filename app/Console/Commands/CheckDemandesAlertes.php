<?php

namespace App\Console\Commands;

use App\Models\Demande;
use App\Models\Notification;
use App\Models\Utilisateur;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckDemandesAlertes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demandes:check-alertes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier les demandes urgentes ou avec pièces manquantes et envoyer des alertes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Vérification des demandes en cours...');

        // 1. Alertes pour les dossiers en attente depuis plus de 3 jours (Dossiers urgents)
        $dateLimite = Carbon::now()->subDays(3);
        $demandesUrgentes = Demande::where('statut', 'soumise')
            ->where('date_soumission', '<', $dateLimite)
            ->get();

        foreach ($demandesUrgentes as $demande) {
            // Notifier le chef traitant s'il y en a un, sinon notifier les chefs de service du même service
            $message = "⚠️ Alerte Urgence : La demande #{$demande->id_demande} ({$demande->typeDemarche->libelle}) est en attente depuis plus de 3 jours. Merci de la traiter rapidement.";

            if ($demande->id_chef_traitant) {
                $this->notifierSaufSiExiste($demande->id_chef_traitant, $message);
            } else {
                // Trouver les chefs de service
                $chefs = Utilisateur::where('role', 'chef_service')
                    ->where('id_service', $demande->fonctionnaire->id_service ?? null)
                    ->get();
                foreach ($chefs as $chef) {
                    $this->notifierSaufSiExiste($chef->id_utilisateur, $message);
                }
            }
        }
        $this->info(count($demandesUrgentes).' demande(s) urgente(s) vérifiée(s).');

        // 2. Alertes pour les dossiers incomplets (pièces manquantes)
        // On suppose que le statut "complement_demande" existe
        $demandesIncompletes = Demande::where('statut', 'complement_demande')
            ->get();

        foreach ($demandesIncompletes as $demande) {
            $message = "⏳ Rappel : Votre demande #{$demande->id_demande} ({$demande->typeDemarche->libelle}) est incomplète. Veuillez fournir les pièces manquantes avant l'expiration du délai.";
            if ($demande->id_fonctionnaire) {
                $this->notifierSaufSiExiste($demande->id_fonctionnaire, $message);
            }
        }
        $this->info(count($demandesIncompletes).' demande(s) incomplète(s) vérifiée(s).');

        $this->info('Vérification terminée avec succès.');
    }

    private function notifierSaufSiExiste($idUtilisateur, $message)
    {
        // Pour éviter de spammer, on vérifie si une notification identique n'a pas été envoyée aujourd'hui
        $existe = Notification::where('id_utilisateur', $idUtilisateur)
            ->where('message', $message)
            ->whereDate('date_envoi', Carbon::today())
            ->exists();

        if (! $existe) {
            Notification::create([
                'id_utilisateur' => $idUtilisateur,
                'message' => $message,
                'lu' => false,
                'date_envoi' => Carbon::now(),
            ]);
            // NB: Pour envoyer un Email ou SMS, on pourrait appeler Mail::to(...)->send(...) ici.
        }
    }
}
