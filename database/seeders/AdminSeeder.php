<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Comptes administrateurs créés (ou mis à jour).
     */
    private const ADMINS = [
        [
            'email' => 'admin@srsp.mg',
            'nom' => 'Admin',
            'prenom' => 'SRSP',
            'password' => 'Admin@2026',
        ],
        [
            'email' => 'adminsrsp@srsp.mg',
            'nom' => 'Admin',
            'prenom' => 'SRSP',
            'password' => 'Admin@2026',
        ],
    ];

    /**
     * Crée (ou met à jour) les comptes administrateurs.
     */
    public function run(): void
    {
        $service = Service::firstOrCreate(
            ['nom_service' => 'Direction des Systèmes d\'Information'],
            ['description' => 'Gestion informatique et numérique']
        );

        foreach (self::ADMINS as $admin) {
            Utilisateur::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'nom' => $admin['nom'],
                    'prenom' => $admin['prenom'],
                    'mot_de_passe' => Hash::make($admin['password']),
                    'role' => 'administrateur',
                    'id_service' => $service->id_service,
                ]
            );
        }

        $this->command->info('Comptes administrateurs créés / mis à jour.');
        $this->command->table(
            ['Rôle', 'Email', 'Mot de passe'],
            array_map(
                fn (array $admin) => ['Administrateur', $admin['email'], $admin['password']],
                self::ADMINS
            )
        );
    }
}
