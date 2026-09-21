<?php

use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Ngrok termina la conexión HTTPS y reenvía la petición al servidor local.
        // Confiar en sus encabezados permite que Laravel genere URLs HTTPS.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias(['permission' => EnsurePermission::class]);

        // Didit calls this endpoint server-to-server, without a Laravel CSRF token.
        $middleware->validateCsrfTokens(except: ['webhooks/didit']);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
