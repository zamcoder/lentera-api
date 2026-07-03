<?php

use App\Http\Middleware\EnsureModerator;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Semua /api/* diperlakukan JSON → 401 tegas saat tak terautentikasi.
        $middleware->api(prepend: [\App\Http\Middleware\ForceJson::class]);

        // Alias middleware Sanctum untuk cek ability token + gerbang moderator.
        $middleware->alias([
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'moderator' => EnsureModerator::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Kembalikan JSON konsisten untuk semua permintaan /api/*.
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*'));

        // Tanpa header Accept, request /api yang tak terautentikasi jangan
        // di-redirect ke route 'login' (tak ada) → balas 401 JSON tegas.
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $e->getMessage()], 401);
            }
        });
    })->create();
