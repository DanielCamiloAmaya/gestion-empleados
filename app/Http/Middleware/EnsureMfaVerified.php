<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMfaVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = auth('admin')->check() ? 'admin' : 'web';
        $actor = auth($guard)->user();

        if ($guard === 'admin'
            && ($actor?->organization?->settings['require_admin_mfa'] ?? false)
            && ! $actor->mfa_enabled
            && ! $request->routeIs('mfa.settings', 'mfa.enable')) {
            return redirect()->route('mfa.settings')->with('error', 'Esta empresa exige MFA para todas las cuentas administrativas.');
        }

        if ($guard === 'web'
            && ($actor?->organization?->settings['require_employee_mfa'] ?? false)
            && ! $actor->mfa_enabled
            && ! $request->routeIs('mfa.settings', 'mfa.enable')) {
            return redirect()->route('mfa.settings')->with('error', 'Esta empresa exige MFA para todas las cuentas de empleado.');
        }

        if ($actor?->mfa_enabled && $request->session()->get('mfa.verified_actor') !== $guard.':'.$actor->getKey()) {
            return redirect()->route('mfa.challenge');
        }

        return $next($request);
    }
}
