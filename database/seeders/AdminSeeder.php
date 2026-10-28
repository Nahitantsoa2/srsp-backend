<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Crée (ou met à jour) un compte administrateur dédié.
     *
     * Identifiants :
     *   Email    : admin@srsp.mg
     *   Mot de passe : Admin@2026
     */
    public function run(): void
    {
        $service = Service::firstOrCreate(
            ['nom_service' => 'Direction des Systèmes d\'Information'],
            ['description' => 'Gestion informatique et numérique']
        );

        Utilisateur::updateOrCreate(
            ['email' => 'admin@srsp.mg'],
            [
                'nom' => 'Admin',
                'prenom' => 'SRSP',
                'mot_de_passe' => Hash::make('Admin@2026'),
                'role' => 'administrateur',
                'id_service' => $service->id_service,
            ]
        );

        $this->command->info('✅ Compte administrateur créé / mis à jour.');
        $this->command->table(
            ['Rôle', 'Email', 'Mot de passe'],
            [
                ['Administrateur', 'admin@srsp.mg', 'Admin@2026'],
            ]
        );
    }
}
