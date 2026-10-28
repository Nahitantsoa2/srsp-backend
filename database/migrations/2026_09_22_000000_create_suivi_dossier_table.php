<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suivi_dossier', function (Blueprint $table) {
            $table->id();
            $table->string('date_reception')->nullable();
            $table->string('ref_be')->nullable();
            $table->string('ref_dossier')->nullable();
            $table->string('im')->nullable();
            $table->string('nom_prenom')->nullable();
            $table->string('ministere')->nullable();
            $table->string('motif_instance')->nullable();
            $table->string('detail_motif_instance')->nullable();
            $table->string('date_leve_motif')->nullable();
            $table->string('info_leve_motif')->nullable();
            $table->string('sort_traitement')->nullable();
            $table->string('motif_rejet')->nullable();
            $table->string('detail_motif_rejet')->nullable();
            $table->string('ref_operation')->nullable();
            $table->string('traitement_augure')->nullable();
            $table->string('date_traitement')->nullable();
            $table->string('type_dossier')->nullable();
            $table->string('nature_dossier')->nullable();
            $table->string('detail_nature_dossier')->nullable();
            $table->string('info_levee_motif')->nullable();
            $table->string('date_leve_motif_2')->nullable();
            $table->string('etat_dossier')->nullable();
            $table->string('motif_rejet_2')->nullable();
            $table->string('user')->nullable();
            $table->string('empreinte', 40)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suivi_dossier');
    }
};
