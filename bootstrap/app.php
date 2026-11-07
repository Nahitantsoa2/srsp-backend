<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\LogRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Journalisation des requêtes HTTP
        $middleware->append(LogRequests::class);

        // Authentification Sanctum
        $middleware->statefulApi();

        // Exclusion CSRF pour les API
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // Middleware de gestion des rôles
        $middleware->alias([
            'role' => CheckRole::class,
        ]);

        // Gestion des utilisateurs non authentifiés
        $middleware->redirectGuestsTo(fn () => null);

    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Réponses JSON pour les routes API
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

    })->create();
