<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HealthController extends Controller
{
    public function index(Request $request)
    {
        $checks = [
            'app' => true,
            'database' => false,
            'sessions_table' => false,
            'session' => false,
            'encryption' => false,
        ];

        $errors = [];

        try {
            DB::connection()->select('SELECT 1');
            $checks['database'] = true;
            $checks['sessions_table'] = Schema::hasTable(config('session.table', 'sessions'));
        } catch (Throwable $e) {
            $errors['database'] = $this->resumeErreur($e);
        }

        try {
            $store = app('session')->driver();
            $store->put('health_probe', 'ok');
            $store->save();
            $checks['session'] = $store->get('health_probe') === 'ok';
        } catch (Throwable $e) {
            $errors['session'] = $this->resumeErreur($e);
        }

        try {
            $encrypter = app('encrypter');
            $checks['encryption'] = $encrypter->decryptString($encrypter->encryptString('ok')) === 'ok';
        } catch (Throwable $e) {
            $errors['encryption'] = $this->resumeErreur($e);
        }

        $healthy = ! in_array(false, $checks, true);

        $payload = [
            'status' => $healthy ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'environment' => app()->environment(),
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ];

        if ($request->boolean('debug') && $errors) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $healthy ? 200 : 503);
    }

    private function resumeErreur(Throwable $e): string
    {
        return class_basename($e).': '.mb_substr($e->getMessage(), 0, 300);
    }
}
