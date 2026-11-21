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
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'active' => \App\Http\Middleware\EnsureActive::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'admin.can' => \App\Http\Middleware\AdminCan::class,
            'admin.stepup' => \App\Http\Middleware\AdminStepUp::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\ImpersonationGuard::class,
            \App\Http\Middleware\TrackVisit::class,
        ]);
        $middleware->validateCsrfTokens(except: ['webhooks/*', 'a/*']);
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
