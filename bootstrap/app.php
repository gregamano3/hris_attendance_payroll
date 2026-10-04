<?php

use App\Features\Privacy\AcknowledgeNotice\RequirePrivacyAcknowledgement;
use App\Shared\TwoFactor\RequireTwoFactor;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Invalidates other sessions when a user changes their password.
        $middleware->appendToGroup('web', AuthenticateSession::class);
        $middleware->appendToGroup('web', RequireTwoFactor::class);
        $middleware->appendToGroup('web', RequirePrivacyAcknowledgement::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
