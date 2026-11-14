<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request): string => route($request->routeIs('home') ? 'tools.index' : 'login'));

        // Called by the browser extension (Bizagi attendance, browser sites) and by Bale,
        // which cannot send a CSRF token. The deploy runner authenticates with its own token.
        $middleware->validateCsrfTokens(except: [
            'attendance-imports',
            'site-imports',
            'bale/webhook',
            'bale/webhook/*',
            'deploy/artisan',
            'deploy/artisan/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
