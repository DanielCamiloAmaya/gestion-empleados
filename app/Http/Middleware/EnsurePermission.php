<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->hasAnyPermission($permissions), 403, 'No tienes permiso para realizar esta operacion.');

        return $next($request);
    }
}
