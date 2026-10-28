<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamation_message', function (Blueprint $table) {
            $table->increments('id_message');
            $table->integer('id_reclamation');
            $table->integer('id_utilisateur');
            $table->text('contenu');
            $table->timestamp('date_envoi')->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->foreign('id_reclamation')->references('id_reclamation')->on('reclamation')->onDelete('cascade');
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur')->onDelete('cascade');
        });

        // Reprise de l'historique existant : description (fonctionnaire) + réponse (administration)
        $admin = DB::selectOne("select id_utilisateur from utilisateur where role = 'administrateur' order by id_utilisateur limit 1");
        $adminId = $admin?->id_utilisateur;

        foreach (DB::table('reclamation')->orderBy('id_reclamation')->get() as $r) {
            if ($r->description !== null && trim($r->description) !== '') {
                DB::table('reclamation_message')->insert([
                    'id_reclamation' => $r->id_reclamation,
                    'id_utilisateur' => $r->id_fonctionnaire,
                    'contenu' => $r->description,
                    'date_envoi' => $r->date_creation ?? now(),
                ]);
            }

            if ($r->reponse !== null && trim($r->reponse) !== '') {
                $auteur = $r->id_chef_traitant ?: $adminId;
                if ($auteur) {
                    DB::table('reclamation_message')->insert([
                        'id_reclamation' => $r->id_reclamation,
                        'id_utilisateur' => $auteur,
                        'contenu' => $r->reponse,
                        'date_envoi' => $r->date_reponse ?? now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamation_message');
    }
};
