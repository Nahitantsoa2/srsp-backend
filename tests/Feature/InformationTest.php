<?php

namespace Tests\Feature;

use App\Models\Information;
use App\Models\Utilisateur;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests des routes « informations » (liste des agents par localité).
 *
 * NB : le schéma de test (SQLite en mémoire) ne rejoue pas les migrations de
 * l'application (certaines dépendent de tables créées hors migrations), on
 * crée donc uniquement la table « information » utile à ces tests.
 */
class InformationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('information');
        Schema::create('information', function (Blueprint $table) {
            $table->id('id_information');
            $table->string('matricule', 30)->unique();
            $table->string('nom', 150);
            $table->string('prenom', 150)->nullable();
            $table->string('localite', 50);
            $table->timestamps();
        });
    }

    /** Connecte un utilisateur (sans persistance) avec le rôle voulu. */
    private function connecter(string $role): void
    {
        Sanctum::actingAs(new Utilisateur(['role' => $role]));
    }

    private function creerAgent(array $attributs = []): Information
    {
        return Information::create(array_merge([
            'matricule' => '509700',
            'nom' => 'RAKOTO',
            'prenom' => 'Jean',
            'localite' => '304',
        ], $attributs));
    }

    // --- Lecture (fonctionnaire) -------------------------------------------

    public function test_index_requiert_une_authentification(): void
    {
        $this->getJson('/api/informations')->assertStatus(401);
    }

    public function test_index_retourne_les_agents_tries_par_localite_puis_nom(): void
    {
        $this->creerAgent(['matricule' => '100', 'nom' => 'ZOLY', 'localite' => '306']);
        $this->creerAgent(['matricule' => '200', 'nom' => 'ANDRA', 'localite' => '304']);
        $this->creerAgent(['matricule' => '300', 'nom' => 'BEMA', 'localite' => '304']);

        $this->connecter('fonctionnaire');

        $this->getJson('/api/informations')
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonPath('0.localite', '304')
            ->assertJsonPath('0.nom', 'ANDRA')
            ->assertJsonPath('1.nom', 'BEMA')
            ->assertJsonPath('2.localite', '306');
    }

    // --- Ajout (administrateur) --------------------------------------------

    public function test_store_est_refuse_a_un_non_administrateur(): void
    {
        $this->connecter('fonctionnaire');

        $this->postJson('/api/admin/informations', [
            'matricule' => '400',
            'nom' => 'NOUVEAU',
            'localite' => '308',
        ])->assertStatus(403);
    }

    public function test_store_cree_un_nouvel_agent(): void
    {
        $this->connecter('administrateur');

        $this->postJson('/api/admin/informations', [
            'matricule' => '400',
            'nom' => 'NOUVEAU',
            'prenom' => 'Paul',
            'localite' => '308',
        ])
            ->assertStatus(201)
            ->assertJsonPath('matricule', '400')
            ->assertJsonPath('nom', 'NOUVEAU');

        $this->assertDatabaseHas('information', ['matricule' => '400']);
    }

    public function test_store_valide_les_champs_obligatoires(): void
    {
        $this->connecter('administrateur');

        $this->postJson('/api/admin/informations', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['matricule', 'nom', 'localite']);
    }

    public function test_store_refuse_un_matricule_deja_utilise(): void
    {
        $this->creerAgent(['matricule' => '509700']);
        $this->connecter('administrateur');

        $this->postJson('/api/admin/informations', [
            'matricule' => '509700',
            'nom' => 'DOUBLON',
            'localite' => '304',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['matricule']);
    }

    // --- Modification (administrateur) -------------------------------------

    public function test_update_modifie_un_agent_existant(): void
    {
        $agent = $this->creerAgent();
        $this->connecter('administrateur');

        $this->patchJson("/api/admin/informations/{$agent->id_information}", [
            'matricule' => '509700',
            'nom' => 'RAKOTO MODIFIE',
            'prenom' => 'Jean',
            'localite' => '324',
        ])
            ->assertOk()
            ->assertJsonPath('nom', 'RAKOTO MODIFIE')
            ->assertJsonPath('localite', '324');

        $this->assertDatabaseHas('information', [
            'id_information' => $agent->id_information,
            'nom' => 'RAKOTO MODIFIE',
        ]);
    }

    public function test_update_autorise_l_agent_a_conserver_son_propre_matricule(): void
    {
        $agent = $this->creerAgent(['matricule' => '509700']);
        $this->connecter('administrateur');

        $this->patchJson("/api/admin/informations/{$agent->id_information}", [
            'matricule' => '509700',
            'nom' => 'MEME MATRICULE',
            'localite' => '304',
        ])->assertOk()->assertJsonPath('nom', 'MEME MATRICULE');
    }

    public function test_update_refuse_le_matricule_d_un_autre_agent(): void
    {
        $this->creerAgent(['matricule' => '111']);
        $agent = $this->creerAgent(['matricule' => '222']);
        $this->connecter('administrateur');

        $this->patchJson("/api/admin/informations/{$agent->id_information}", [
            'matricule' => '111',
            'nom' => 'CONFLIT',
            'localite' => '304',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['matricule']);
    }

    public function test_update_retourne_404_si_absent(): void
    {
        $this->connecter('administrateur');

        $this->patchJson('/api/admin/informations/999999', [
            'matricule' => '999999',
            'nom' => 'FANTOME',
            'localite' => '304',
        ])->assertStatus(404);
    }

    // --- Suppression (administrateur) --------------------------------------

    public function test_destroy_supprime_un_agent(): void
    {
        $agent = $this->creerAgent();
        $this->connecter('administrateur');

        $this->deleteJson("/api/admin/informations/{$agent->id_information}")
            ->assertOk();

        $this->assertDatabaseMissing('information', [
            'id_information' => $agent->id_information,
        ]);
    }

    public function test_destroy_retourne_404_si_absent(): void
    {
        $this->connecter('administrateur');

        $this->deleteJson('/api/admin/informations/999999')->assertStatus(404);
    }
}
