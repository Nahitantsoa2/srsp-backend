<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\TypeDemarche;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // --- Services ---
        // firstOrCreate : ne recrée pas le service s'il existe déjà (par nom)
        $drh = Service::firstOrCreate(
            ['nom_service' => 'Direction des Ressources Humaines'],
            ['description' => 'Gestion du personnel et des carrières']
        );

        $dsi = Service::firstOrCreate(
            ['nom_service' => 'Direction des Systèmes d\'Information'],
            ['description' => 'Gestion informatique et numérique']
        );

        // --- Types de démarches ---
        TypeDemarche::firstOrCreate(
            ['libelle' => 'Demande de pension', 'id_service' => $drh->id_service],
            ['description' => 'Demande de pension']
        );

        TypeDemarche::firstOrCreate(
            ['libelle' => 'Demande de complément d\'installation', 'id_service' => $drh->id_service],
            ['description' => 'Demande d\'autorisation de percevoir le complément d\'installation']
        );

        TypeDemarche::firstOrCreate(
            ['libelle' => 'Demande de Duplicata', 'id_service' => $drh->id_service],
            ['description' => 'Demande de Duplicata (Avis d\'Emission / Ordre de Recette / Etat de décompte)']
        );

        TypeDemarche::firstOrCreate(
            ['libelle' => 'Demande de Validation des services', 'id_service' => $drh->id_service],
            ['description' => 'Demande de Validation des services précaires']
        );

        TypeDemarche::firstOrCreate(
            ['libelle' => 'Demande de Bon de Caisse', 'id_service' => $drh->id_service],
            ['description' => 'Demande de Bon de Caisse']
        );

        // --- Utilisateurs de test (les 3 rôles) ---
        // updateOrCreate : si l'email existe déjà, met à jour les autres champs
        // au lieu de planter avec une erreur de contrainte unique.

        Utilisateur::updateOrCreate(
            ['email' => 'fonctionnaire@test.com'],
            [
                'nom' => 'Rakoto',
                'prenom' => 'Jean',
                'mot_de_passe' => Hash::make('password'),
                'role' => 'fonctionnaire',
                'id_service' => $drh->id_service,
                // Matricule (IM) de démonstration : doit exister dans
                // frontend/src/data/suiviDossiers.json pour que la page
                // Suivi affiche les dossiers réels de cet agent.
                'matricule' => '482296',
            ]
        );

        Utilisateur::updateOrCreate(
            ['email' => 'chef@test.com'],
            [
                'nom' => 'Rabe',
                'prenom' => 'Marie',
                'mot_de_passe' => Hash::make('password'),
                'role' => 'chef_service',
                'id_service' => $drh->id_service,
            ]
        );

        Utilisateur::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'nom' => 'Andria',
                'prenom' => 'Paul',
                'mot_de_passe' => Hash::make('password'),
                'role' => 'administrateur',
                'id_service' => $dsi->id_service,
            ]
        );

        $this->command->info('✅ Données de démonstration créées (ou mises à jour) avec succès.');
        $this->command->table(
            ['Rôle', 'Email', 'Mot de passe'],
            [
                ['Fonctionnaire', 'fonctionnaire@test.com', 'password'],
                ['Chef de service', 'chef@test.com', 'password'],
                ['Administrateur', 'admin@test.com', 'password'],
            ]
        );
    }
}
