<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamation_message_masque', function (Blueprint $table) {
            $table->increments('id_masque');
            $table->integer('id_message');
            $table->integer('id_utilisateur');

            $table->unique(['id_message', 'id_utilisateur']);

            $table->foreign('id_message')->references('id_message')->on('reclamation_message')->onDelete('cascade');
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamation_message_masque');
    }
};
