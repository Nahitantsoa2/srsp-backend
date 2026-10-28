<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Utilisateur;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests des routes « notifications » (marquage lu + suppression).
 */
class NotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('notification');
        Schema::dropIfExists('utilisateur');

        Schema::create('utilisateur', function (Blueprint $table) {
            $table->id('id_utilisateur');
            $table->string('nom')->nullable();
            $table->string('prenom')->nullable();
            $table->string('email')->nullable();
            $table->string('mot_de_passe')->nullable();
            $table->string('role')->nullable();
            $table->string('matricule')->nullable();
        });

        Schema::create('notification', function (Blueprint $table) {
            $table->id('id_notification');
            $table->unsignedBigInteger('id_utilisateur');
            $table->text('message');
            $table->boolean('lu')->default(false);
            $table->timestamp('date_envoi')->useCurrent();
        });
    }

    /** Connecte un utilisateur (sans persistance) avec l'identifiant voulu. */
    private function connecter(int $idUtilisateur): Utilisateur
    {
        $utilisateur = new Utilisateur(['role' => 'fonctionnaire']);
        $utilisateur->id_utilisateur = $idUtilisateur;
        Sanctum::actingAs($utilisateur);

        return $utilisateur;
    }

    private function creerNotification(int $idUtilisateur, array $attributs = []): Notification
    {
        return Notification::create(array_merge([
            'id_utilisateur' => $idUtilisateur,
            'message' => 'Votre dossier a été rejeté',
            'lu' => false,
        ], $attributs));
    }

    // --- Suppression --------------------------------------------------------

    public function test_supprimer_retire_la_notification_de_la_base(): void
    {
        $this->connecter(1);
        $notification = $this->creerNotification(1);

        $this->deleteJson("/api/notifications/{$notification->id_notification}")
            ->assertOk();

        $this->assertDatabaseMissing('notification', [
            'id_notification' => $notification->id_notification,
        ]);
    }

    public function test_supprimer_ne_touche_pas_aux_notifications_des_autres(): void
    {
        $this->connecter(1);
        $autre = $this->creerNotification(2);

        $this->deleteJson("/api/notifications/{$autre->id_notification}")
            ->assertStatus(404);

        $this->assertDatabaseHas('notification', [
            'id_notification' => $autre->id_notification,
        ]);
    }

    public function test_supprimer_une_notification_inexistante_renvoie_404(): void
    {
        $this->connecter(1);

        $this->deleteJson('/api/notifications/999999')->assertStatus(404);
    }

    public function test_supprimer_requiert_une_authentification(): void
    {
        $notification = $this->creerNotification(1);

        $this->deleteJson("/api/notifications/{$notification->id_notification}")
            ->assertStatus(401);
    }
}
