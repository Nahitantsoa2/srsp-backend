<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function index()
    {
        $checks = [
            'app' => true,
            'database' => false,
        ];

        try {
            DB::connection()->select('SELECT 1');
            $checks['database'] = true;
        } catch (Throwable) {
            $checks['database'] = false;
        }

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'environment' => app()->environment(),
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
