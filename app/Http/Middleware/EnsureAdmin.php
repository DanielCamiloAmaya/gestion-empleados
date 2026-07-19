<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('admin')->check() || (auth('admin')->user()->status ?? 'active') !== 'active') {
            auth('admin')->logout();

            return redirect()->route('admin.login')->with('error', 'Se requieren permisos de Recursos Humanos.');
        }

        return $next($request);
    }
}
