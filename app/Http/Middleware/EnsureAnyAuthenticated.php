<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnyAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() && ! auth('admin')->check()) {
            return redirect()->route('login')->with('error', 'Inicia sesión para continuar.');
        }

        return $next($request);
    }
}
