<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * Liveness: the process is up and serving requests.
     */
    public function liveness(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => config('app.name', 'laravel-taskboard'),
            'release' => config('app.release', 'dev'),
            'time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Readiness: required dependencies (database) must be reachable.
     */
    public function readiness(): JsonResponse
    {
        try {
            $dbReachable = Cache::store('array')->remember('health:db', 5, function (): bool {
                DB::select('select 1');

                return true;
            });
        } catch (Throwable) {
            $dbReachable = false;
        }

        if (! $dbReachable) {
            return response()->json([
                'status' => 'unavailable',
                'checks' => ['database' => 'down'],
            ], 503);
        }

        return response()->json([
            'status' => 'ready',
            'checks' => ['database' => 'up'],
        ]);
    }

    /**
     * Non-sensitive build/release marker to distinguish deployed revisions.
     */
    public function version(): JsonResponse
    {
        return response()->json([
            'app' => config('app.name', 'laravel-taskboard'),
            'framework' => app()->version(),
            'php' => PHP_VERSION,
            'release' => config('app.release', 'dev'),
            'commit' => config('app.commit'),
            'environment' => app()->environment(),
        ]);
    }
}
