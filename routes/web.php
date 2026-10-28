<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
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
