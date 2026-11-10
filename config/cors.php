<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(
            ',',
            env(
                'CORS_ALLOWED_ORIGINS',
                'http://localhost:5173,http://127.0.0.1:5173,https://srsp-frontend-chi.vercel.app'
            )
        )
    ))),

    'allowed_origins_patterns' => array_values(array_filter([
        '#^https://.*\.vercel\.app$#',
        // En developpement, le port de Vite peut changer (5173, 5174, ...).
        // On autorise donc tout port local uniquement hors production.
        env('APP_ENV', 'production') === 'local'
            ? '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#'
            : null,
    ])),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
