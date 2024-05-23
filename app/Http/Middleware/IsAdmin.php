<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            // Si el usuario no está autenticado, redirigir al login
            return redirect('/login');
        }

        if (!Auth::user()->is_admin) {
            // Si el usuario no es un administrador, mostrar un error o redirigir a una página específica
            return redirect('/')->with('error', 'No tienes permiso para realizar esta acción.');
        }

        return $next($request);
    }
}




