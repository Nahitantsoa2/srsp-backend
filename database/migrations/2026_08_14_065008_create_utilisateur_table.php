<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateur', function (Blueprint $table) {
            $table->id('id_utilisateur');
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('email', 150)->unique();
            $table->string('mot_de_passe', 255);

            // Sur PostgreSQL, Laravel traduit enum() en VARCHAR + contrainte CHECK
            $table->enum('role', ['fonctionnaire', 'chef_service', 'administrateur'])
                  ->default('fonctionnaire');

            $table->timestamp('date_creation')->useCurrent();

            $table->foreignId('id_service')
                  ->constrained('service', 'id_service')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateur');
    }
};