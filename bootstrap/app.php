<?php

use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\EnforceSessionTimeouts;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureAnyAuthenticated;
use App\Http\Middleware\EnsureMfaVerified;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsurePlanFeature;
use App\Http\Middleware\EnsurePlatformMfa;
use App\Http\Middleware\EnsurePlatformUser;
use App\Http\Middleware\RequestCorrelation;
use App\Http\Middleware\ResolveOrganization;
use App\Http\Middleware\SecurityHeaders;
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
        $middleware->append(RequestCorrelation::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [ResolveOrganization::class, EnforceSessionTimeouts::class]);
        $middleware->alias([
            'authenticated.any' => EnsureAnyAuthenticated::class,
            'admin' => EnsureAdmin::class,
            'permission' => EnsurePermission::class,
            'plan' => EnsurePlanFeature::class,
            'mfa' => EnsureMfaVerified::class,
            'api.token' => AuthenticateApiToken::class,
            'platform' => EnsurePlatformUser::class,
            'platform.mfa' => EnsurePlatformMfa::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
