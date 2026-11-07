<?php

use App\Http\Controllers\AgentIaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReclamationController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StatistiquesController;
use App\Http\Controllers\SuiviDossierController;
use App\Http\Controllers\TypeDemarcheController;
use App\Http\Controllers\UtilisateurController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques (pas d'authentification requise)
|--------------------------------------------------------------------------
*/

// Etat de sante de l'API et de la base de donnees
Route::get('/health', [HealthController::class, 'index'])->name('health');

// Connexion et inscription
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:30,1')
    ->name('login');

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:30,1');

// Services accessibles publiquement
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Routes protégées (utilisateur connecté, tous rôles confondus)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Profil et session
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/moi', [AuthController::class, 'moi']);

    // Types de démarches
    Route::get('/types-demarche', [TypeDemarcheController::class, 'index']);

    // Demandes et démarches
    Route::post('/demandes', [DemandeController::class, 'store']);
    Route::post('/demandes/ia', [DemandeController::class, 'creerAvecIA']);
    Route::get('/demandes/mes-demandes', [DemandeController::class, 'mesDemandes']);
    Route::get('/demandes/{id}', [DemandeController::class, 'show']);

    // Réclamations
    Route::post('/reclamations', [ReclamationController::class, 'store']);
    Route::get('/reclamations/mes-reclamations', [ReclamationController::class, 'mesReclamations']);
    Route::post('/reclamations/{id}/messages', [ReclamationController::class, 'ajouterMessage']);
    Route::delete('/reclamations/{id}/messages/{messageId}', [ReclamationController::class, 'supprimerMessage']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/lu', [NotificationController::class, 'marquerLu']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Suivi des dossiers
    Route::get('/suivi', [SuiviDossierController::class, 'index']);

    // Informations
    Route::get('/informations', [InformationController::class, 'index']);

    // Conversations avec l'assistant IA
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::delete('/conversations', [ConversationController::class, 'destroyAll']);
    Route::delete('/conversations/{id}', [ConversationController::class, 'destroy']);
    Route::get('/conversations/{id}/messages', [ConversationController::class, 'messages']);
    Route::post('/conversations/{id}/messages', [ConversationController::class, 'envoyerMessage']);

    /*
    |--------------------------------------------------------------------------
    | Routes réservées au Chef de service
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:chef_service')
        ->prefix('chef')
        ->group(function () {

            // Traitement des demandes
            Route::get('/demandes', [DemandeController::class, 'demandesAtraiter']);
            Route::patch('/demandes/{id}/traiter', [DemandeController::class, 'traiter']);

            // Gestion des réclamations
            Route::get('/reclamations', [ReclamationController::class, 'reclamationsRecues']);
            Route::patch('/reclamations/{id}/repondre', [ReclamationController::class, 'repondre']);

            // Tableau de bord
            Route::get('/tableau-de-bord', [DemandeController::class, 'statistiques']);
        });

    /*
    |--------------------------------------------------------------------------
    | Routes réservées à l'Administrateur
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:administrateur')
        ->prefix('admin')
        ->group(function () {

            // Gestion des utilisateurs
            Route::apiResource('/utilisateurs', UtilisateurController::class);

            // Gestion des agents IA
            Route::get('/agents', [AgentIaController::class, 'index']);
            Route::patch('/agents/{id}', [AgentIaController::class, 'update']);

            // Gestion des services
            Route::post('/services', [ServiceController::class, 'store']);
            Route::patch('/services/{id}', [ServiceController::class, 'update']);
            Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

            // Gestion des types de démarches
            Route::post('/types-demarche', [TypeDemarcheController::class, 'store']);

            // Statistiques
            Route::get('/statistiques', [StatistiquesController::class, 'index']);

            // Gestion des réclamations
            Route::get('/reclamations', [ReclamationController::class, 'adminReclamations']);
            Route::patch('/reclamations/{id}/repondre', [ReclamationController::class, 'repondre']);

            // Import et gestion du suivi des dossiers
            Route::post('/suivi/import', [SuiviDossierController::class, 'import']);
            Route::get('/suivi/modele', [SuiviDossierController::class, 'telechargerModele']);
            Route::post('/suivi', [SuiviDossierController::class, 'store']);
            Route::delete('/suivi/{id}', [SuiviDossierController::class, 'destroy']);
            Route::delete('/suivi', [SuiviDossierController::class, 'destroyAll']);

            // Gestion des informations
            Route::post('/informations', [InformationController::class, 'store']);
            Route::patch('/informations/{id}', [InformationController::class, 'update']);
            Route::delete('/informations/{id}', [InformationController::class, 'destroy']);
        });
});
