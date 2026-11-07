<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $debut = microtime(true);

        try {
            $response = $next($request);

            $this->log($request, $response->getStatusCode(), $debut);

            return $response;
        } catch (Throwable $e) {
            $this->log($request, 500, $debut, $e);

            throw $e;
        }
    }

    private function log(Request $request, int $status, float $debut, ?Throwable $e = null): void
    {
        $duree = (int) round((microtime(true) - $debut) * 1000);

        $contexte = [
            'method' => $request->method(),
            'path' => $request->getPathInfo(),
            'status' => $status,
            'duration_ms' => $duree,
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 200),
        ];

        if ($e) {
            $contexte['exception'] = class_basename($e).': '.mb_substr($e->getMessage(), 0, 300);
        }

        Log::info('HTTP '.$contexte['method'].' '.$contexte['path'].' '.$status, $contexte);
    }
}
