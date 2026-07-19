<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformUser
{
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = auth('platform')->user();
        if (! $user || ! $user->isActive()) {
            auth('platform')->logout();

            return redirect()->route('control.login')->with('error', 'Inicia sesión con una cuenta interna activa.');
        }

        if ($permission && ! $user->hasPermission($permission)) {
            abort(403, 'Tu función interna no permite realizar esta operación.');
        }

        return $next($request);
    }
}
