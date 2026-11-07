<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'app_key' => !empty(config('app.key')),
        'env' => config('app.env'),
    ]);
});

Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'message' => 'API SRSP Assistant IA',
        'health' => url('/api/health'),
    ]);
});

// Route de diagnostic en HTTP 200
Route::get('/debug-db', function () {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $tables = \Illuminate\Support\Facades\DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
        return response()->json([
            'statut' => '✅ Connexion réussie',
            'tables' => array_map(fn ($t) => $t->table_name, $tables),
        ], 200);
    } catch (\Throwable $e) {
        return response()->json([
            'statut' => '❌ Échec',
            'erreur' => $e->getMessage(),
            'classe' => get_class($e),
            'db_config' => [
                'connection' => config('database.default'),
                'host' => config('database.connections.pgsql.host'),
                'port' => config('database.connections.pgsql.port'),
                'database' => config('database.connections.pgsql.database'),
                'username' => config('database.connections.pgsql.username'),
                'has_password' => !empty(config('database.connections.pgsql.password')),
                'url' => config('database.connections.pgsql.url') ? 'défini' : 'non défini',
                'app_key_present' => !empty(config('app.key')),
            ],
        ], 200);
    }
});

// Route de test — à supprimer une fois la connexion confirmée
Route::get('/test-db', function () {
    try {
        DB::connection()->getPdo();

        $tables = DB::select("
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = 'public'
            ORDER BY table_name
        ");

        return response()->json([
            'statut'          => '✅ Connexion réussie',
            'base_active'     => DB::connection()->getDatabaseName(),
            'hote'            => config('database.connections.pgsql.host'),
            'port'            => config('database.connections.pgsql.port'),
            'nombre_tables'   => count($tables),
            'tables'          => array_map(fn ($t) => $t->table_name, $tables),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'statut'  => '❌ Échec de connexion',
            'erreur'  => $e->getMessage(),
        ], 500);
    }
});
