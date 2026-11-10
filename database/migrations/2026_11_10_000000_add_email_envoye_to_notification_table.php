<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute le suivi de l'envoi email sur les notifications.
     * Permet de créer la notification in-app même si l'email échoue,
     * puis de réessayer l'email à la prochaine connexion.
     */
    public function up(): void
    {
        if (Schema::hasTable('notification') && ! Schema::hasColumn('notification', 'email_envoye')) {
            Schema::table('notification', function (Blueprint $table) {
                $table->boolean('email_envoye')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notification', 'email_envoye')) {
            Schema::table('notification', function (Blueprint $table) {
                $table->dropColumn('email_envoye');
            });
        }
    }
};
