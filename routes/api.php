<?php

use App\Http\Controllers\AgentIaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DemandeController;
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

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1');

// Public : nécessaire pour peupler le menu déroulant du formulaire d'inscription
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Routes protégées (utilisateur connecté, tous rôles confondus)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // --- Profil / session ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/moi', [AuthController::class, 'moi']);

    // --- Types de démarches (liste utilisée par le formulaire manuel + l'IA) ---
    Route::get('/types-demarche', [TypeDemarcheController::class, 'index']);

    // --- Démarches (fonctionnaire) ---
    Route::post('/demandes', [DemandeController::class, 'store']);
    Route::post('/demandes/ia', [DemandeController::class, 'creerAvecIA']);
    Route::get('/demandes/mes-demandes', [DemandeController::class, 'mesDemandes']);
    Route::get('/demandes/{id}', [DemandeController::class, 'show']);

    // --- Réclamations (fonctionnaire) ---
    Route::post('/reclamations', [ReclamationController::class, 'store']);
    Route::get('/reclamations/mes-reclamations', [ReclamationController::class, 'mesReclamations']);
    Route::post('/reclamations/{id}/messages', [ReclamationController::class, 'ajouterMessage']);
    Route::delete('/reclamations/{id}/messages/{messageId}', [ReclamationController::class, 'supprimerMessage']);

    // --- Notifications ---
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/lu', [NotificationController::class, 'marquerLu']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // --- Suivi des dossiers (lecture pour le fonctionnaire) ---
    Route::get('/suivi', [SuiviDossierController::class, 'index']);

    // --- Informations (liste des agents par localité) — lecture ---
    Route::get('/informations', [InformationController::class, 'index']);

    // --- Conversations avec l'agent d'assistance (LLM) ---
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::delete('/conversations', [ConversationController::class, 'destroyAll']);
    Route::delete('/conversations/{id}', [ConversationController::class, 'destroy']);
    Route::get('/conversations/{id}/messages', [ConversationController::class, 'messages']);
    Route::post('/conversations/{id}/messages', [ConversationController::class, 'envoyerMessage']);

    /*
    |----------------------------------------------------------------------
    | Routes réservées au Chef de service
    |----------------------------------------------------------------------
    */
    Route::middleware('role:chef_service')->prefix('chef')->group(function () {
        Route::get('/demandes', [DemandeController::class, 'demandesAtraiter']);
        Route::patch('/demandes/{id}/traiter', [DemandeController::class, 'traiter']);
        Route::get('/reclamations', [ReclamationController::class, 'reclamationsRecues']);
        Route::patch('/reclamations/{id}/repondre', [ReclamationController::class, 'repondre']);
        Route::get('/tableau-de-bord', [DemandeController::class, 'statistiques']);
    });

    /*
    |----------------------------------------------------------------------
    | Routes réservées à l'Administrateur
    |----------------------------------------------------------------------
    */
    Route::middleware('role:administrateur')->prefix('admin')->group(function () {
        Route::apiResource('/utilisateurs', UtilisateurController::class);
        Route::get('/agents', [AgentIaController::class, 'index']);
        Route::patch('/agents/{id}', [AgentIaController::class, 'update']);

        Route::post('/services', [ServiceController::class, 'store']);
        Route::patch('/services/{id}', [ServiceController::class, 'update']);
        Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

        Route::post('/types-demarche', [TypeDemarcheController::class, 'store']);

        // --- Statistiques (admin) ---
        Route::get('/statistiques', [StatistiquesController::class, 'index']);

        // --- Réclamations / messages (admin) ---
        Route::get('/reclamations', [ReclamationController::class, 'adminReclamations']);
        Route::patch('/reclamations/{id}/repondre', [ReclamationController::class, 'repondre']);

        // --- Import des dossiers de suivi (Excel/CSV hebdomadaire) ---
        Route::post('/suivi/import', [SuiviDossierController::class, 'import']);

        // --- Gestion des informations (liste des agents) ---
        Route::post('/informations', [InformationController::class, 'store']);
        Route::patch('/informations/{id}', [InformationController::class, 'update']);
        Route::delete('/informations/{id}', [InformationController::class, 'destroy']);
    });
});
