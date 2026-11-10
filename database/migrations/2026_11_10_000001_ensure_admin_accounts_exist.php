<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Comptes administrateurs garantis présents après `php artisan migrate`
     * (notamment sur Render, où le seeder n'est pas exécuté automatiquement).
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

    public function up(): void
    {
        $serviceId = DB::table('service')
            ->where('nom_service', 'Direction des Systèmes d\'Information')
            ->value('id_service');

        if (! $serviceId) {
            $serviceId = DB::table('service')->insertGetId([
                'nom_service' => 'Direction des Systèmes d\'Information',
                'description' => 'Gestion informatique et numérique',
            ]);
        }

        foreach (self::ADMINS as $admin) {
            DB::table('utilisateur')->updateOrInsert(
                ['email' => $admin['email']],
                [
                    'nom' => $admin['nom'],
                    'prenom' => $admin['prenom'],
                    'mot_de_passe' => Hash::make($admin['password']),
                    'role' => 'administrateur',
                    'id_service' => $serviceId,
                    'matricule' => null,
                ]
            );
        }
    }

    public function down(): void
    {
        // Les comptes administrateurs ne sont pas supprimés lors d'un rollback.
    }
};
