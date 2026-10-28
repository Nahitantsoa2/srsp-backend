<?php

namespace Tests\Feature;

use App\Mail\DossierRejete;
use App\Models\SuiviDossier;
use App\Models\Utilisateur;
use App\Services\RejetDossierNotifier;
use App\Services\SuiviImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests du rejet de dossier : détection (SuiviDossier::estRejete) et
 * notification par email + in-app (RejetDossierNotifier) à l'import du suivi.
 *
 * Comme pour InformationTest, le schéma de test ne rejoue pas les migrations de
 * l'application : on ne crée que les tables nécessaires à ces tests.
 */
class SuiviRejetTest extends TestCase
{
    private const COLONNES_SUIVI = [
        'date_reception', 'ref_be', 'ref_dossier', 'im', 'nom_prenom', 'ministere',
        'motif_instance', 'detail_motif_instance', 'date_leve_motif', 'info_leve_motif',
        'sort_traitement', 'motif_rejet', 'detail_motif_rejet', 'ref_operation',
        'traitement_augure', 'date_traitement', 'type_dossier', 'nature_dossier',
        'detail_nature_dossier', 'info_levee_motif', 'date_leve_motif_2', 'etat_dossier',
        'motif_rejet_2', 'user',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('notification');
        Schema::dropIfExists('suivi_dossier');
        Schema::dropIfExists('utilisateur');

        Schema::create('utilisateur', function (Blueprint $table) {
            $table->id('id_utilisateur');
            $table->string('nom')->nullable();
            $table->string('prenom')->nullable();
            $table->string('email')->unique();
            $table->string('mot_de_passe')->nullable();
            $table->string('role')->nullable();
            $table->unsignedBigInteger('id_service')->nullable();
            $table->string('matricule')->nullable();
        });

        Schema::create('notification', function (Blueprint $table) {
            $table->id('id_notification');
            $table->unsignedBigInteger('id_utilisateur');
            $table->text('message');
            $table->boolean('lu')->default(false);
            $table->timestamp('date_envoi')->nullable();
        });

        Schema::create('suivi_dossier', function (Blueprint $table) {
            $table->id();
            foreach (self::COLONNES_SUIVI as $colonne) {
                $table->string($colonne)->nullable();
            }
            $table->string('empreinte', 40)->unique();
            $table->timestamps();
        });
    }

    private function creerFonctionnaire(array $attributs = []): Utilisateur
    {
        return Utilisateur::create(array_merge([
            'nom' => 'RAKOTO',
            'prenom' => 'Jean',
            'email' => 'fonctionnaire@test.com',
            'role' => 'fonctionnaire',
            'matricule' => '482296',
        ], $attributs));
    }

    private function dossier(array $attributs = []): SuiviDossier
    {
        return new SuiviDossier(array_merge([
            'im' => '482296',
            'ref_dossier' => '1805',
            'sort_traitement' => 'Traite avec rejet',
            'motif_rejet' => '00',
        ], $attributs));
    }

    // --- Détection du rejet -------------------------------------------------

    public function test_est_rejete_detecte_les_sorts_de_traitement_contenant_rejet(): void
    {
        foreach (['Traite avec rejet', 'REJET', 'Rejeté', 'traite-rejet'] as $sort) {
            $this->assertTrue(
                $this->dossier(['sort_traitement' => $sort])->estRejete(),
                "Attendu rejeté pour : {$sort}"
            );
        }
    }

    public function test_est_rejete_ignore_les_dossiers_non_rejetes(): void
    {
        foreach (['Traite sans rejet', 'sans rejet', 'En cours', 'Terminer', ''] as $sort) {
            $this->assertFalse(
                $this->dossier(['sort_traitement' => $sort])->estRejete(),
                "Attendu NON rejeté pour : {$sort}"
            );
        }
    }

    // --- Notification -------------------------------------------------------

    public function test_notifier_envoie_email_et_notification_in_app(): void
    {
        Mail::fake();
        $fonctionnaire = $this->creerFonctionnaire();

        $nombre = app(RejetDossierNotifier::class)->notifier([$this->dossier()]);

        $this->assertSame(1, $nombre);
        Mail::assertSent(DossierRejete::class, fn ($mail) => $mail->hasTo('fonctionnaire@test.com'));
        $this->assertDatabaseHas('notification', [
            'id_utilisateur' => $fonctionnaire->id_utilisateur,
        ]);
    }

    public function test_notifier_ignore_un_dossier_non_rejete(): void
    {
        Mail::fake();
        $this->creerFonctionnaire();

        $nombre = app(RejetDossierNotifier::class)->notifier([
            $this->dossier(['sort_traitement' => 'Traite sans rejet']),
        ]);

        $this->assertSame(0, $nombre);
        Mail::assertNothingSent();
        $this->assertDatabaseCount('notification', 0);
    }

    public function test_notifier_ignore_si_aucun_fonctionnaire_ne_correspond_a_l_im(): void
    {
        Mail::fake();
        $this->creerFonctionnaire(['matricule' => '999999']);

        $nombre = app(RejetDossierNotifier::class)->notifier([$this->dossier(['im' => '482296'])]);

        $this->assertSame(0, $nombre);
        Mail::assertNothingSent();
    }

    public function test_notifier_ignore_les_utilisateurs_non_fonctionnaires(): void
    {
        Mail::fake();
        $this->creerFonctionnaire(['role' => 'chef_service']);

        $nombre = app(RejetDossierNotifier::class)->notifier([$this->dossier()]);

        $this->assertSame(0, $nombre);
        Mail::assertNothingSent();
    }

    public function test_notifier_pour_fonctionnaire_avertit_ses_dossiers_rejetes(): void
    {
        Mail::fake();
        $fonctionnaire = $this->creerFonctionnaire();

        SuiviDossier::create([
            'im' => '482296',
            'ref_dossier' => '1805',
            'sort_traitement' => 'Traite avec rejet',
            'motif_rejet' => '00',
            'empreinte' => 'empreinte-connexion-1',
        ]);

        $nombre = app(RejetDossierNotifier::class)->notifierPourFonctionnaire($fonctionnaire);

        $this->assertSame(1, $nombre);
        Mail::assertSent(DossierRejete::class, fn ($mail) => $mail->hasTo('fonctionnaire@test.com'));
        $this->assertDatabaseHas('notification', [
            'id_utilisateur' => $fonctionnaire->id_utilisateur,
        ]);
    }

    public function test_notifier_pour_fonctionnaire_ne_renvoie_pas_les_doublons(): void
    {
        Mail::fake();
        $fonctionnaire = $this->creerFonctionnaire();

        SuiviDossier::create([
            'im' => '482296',
            'ref_dossier' => '1805',
            'sort_traitement' => 'REJET',
            'motif_rejet' => '00',
            'empreinte' => 'empreinte-connexion-2',
        ]);

        $notifier = app(RejetDossierNotifier::class);

        $this->assertSame(1, $notifier->notifierPourFonctionnaire($fonctionnaire));
        $this->assertSame(0, $notifier->notifierPourFonctionnaire($fonctionnaire));
        $this->assertDatabaseCount('notification', 1);
        Mail::assertSent(DossierRejete::class, 1);
    }

    public function test_le_mailable_du_rejet_rend_les_infos_du_dossier(): void
    {
        $fonctionnaire = $this->creerFonctionnaire();

        $html = (new DossierRejete($this->dossier(), $fonctionnaire))->render();

        $this->assertStringContainsString('1805', $html);
        $this->assertStringContainsString('RAKOTO', $html);
    }

    // --- Import -------------------------------------------------------------

    public function test_import_retourne_les_nouveaux_dossiers_et_declenche_la_notification(): void
    {
        Mail::fake();
        $this->creerFonctionnaire();

        $chemin = $this->creerCsvSuivi();
        $resultat = app(SuiviImportService::class)->importer($chemin);
        @unlink($chemin);

        $this->assertSame(1, $resultat['ajoutes']);
        $this->assertCount(1, $resultat['nouveaux']);
        $this->assertTrue($resultat['nouveaux'][0]->estRejete());

        $nombre = app(RejetDossierNotifier::class)->notifier($resultat['nouveaux']);
        $this->assertSame(1, $nombre);
        Mail::assertSent(DossierRejete::class);
    }

    /** Écrit un CSV de suivi temporaire avec une ligne rejetée (IM 482296). */
    private function creerCsvSuivi(): string
    {
        $ligne = array_fill(0, count(self::COLONNES_SUIVI), '');
        $ligne[3] = '482296';            // im
        $ligne[10] = 'Traite avec rejet'; // sort_traitement
        $ligne[11] = '00';               // motif_rejet

        $chemin = storage_path('app/test_suivi_rejet.csv');
        file_put_contents($chemin, implode(',', self::COLONNES_SUIVI)."\n".implode(',', $ligne));

        return $chemin;
    }
}
