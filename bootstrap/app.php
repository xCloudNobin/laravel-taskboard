<?php

use App\Http\Controllers\HealthController;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Health endpoints intentionally bypass the session/CSRF web
            // middleware so liveness reflects only the process state and
            // readiness performs its own explicit database check.
            Route::get('/health/live', [HealthController::class, 'liveness']);
            Route::get('/health/ready', [HealthController::class, 'readiness']);
            Route::get('/version', [HealthController::class, 'version']);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
