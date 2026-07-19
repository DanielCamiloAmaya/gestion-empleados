<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionTimeouts
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('platform')->check()
            ? 'platform'
            : (Auth::guard('admin')->check() ? 'admin' : (Auth::guard('web')->check() ? 'web' : null));

        if ($guard === null) {
            return $next($request);
        }

        $now = now()->timestamp;
        $version = (int) config('session_security.version');
        $authenticatedAt = $request->session()->get('security.authenticated_at');
        $lastActivityAt = $request->session()->get('security.last_activity_at');
        $actor = Auth::guard($guard)->user();

        if (! is_numeric($authenticatedAt) || ! is_numeric($lastActivityAt)) {
            $request->session()->put([
                'security.guard' => $guard,
                'security.version' => $version,
                'security.authenticated_at' => $now,
                'security.last_activity_at' => $now,
                'security.auth_version' => (int) ($actor->auth_version ?? 1),
            ]);

            return $next($request);
        }

        if ((int) $request->session()->get('security.version') !== $version
            || $request->session()->get('security.guard') !== $guard) {
            return $this->expire($request, $guard, 'Tu sesión cambió y debes autenticarte nuevamente.');
        }

        if (! $request->session()->has('security.auth_version')) {
            $request->session()->put('security.auth_version', (int) ($actor->auth_version ?? 1));
        } elseif ((int) $request->session()->get('security.auth_version') !== (int) ($actor->auth_version ?? 1)) {
            return $this->expire($request, $guard, 'Tus credenciales cambiaron. Inicia sesión nuevamente.');
        }

        if ($guard === 'web' && ! in_array($actor->employment_status, ['active', 'onboarding'], true)) {
            return $this->expire($request, $guard, 'Tu cuenta laboral ya no está activa.');
        }

        if ($guard === 'admin' && ($actor->status ?? 'active') !== 'active') {
            return $this->expire($request, $guard, 'Tu cuenta administrativa ya no está activa.');
        }

        $idleMinutes = (int) config("session_security.{$this->guardPrefix($guard)}_idle_minutes");
        $absoluteMinutes = (int) config("session_security.{$this->guardPrefix($guard)}_absolute_minutes");

        if ($now - (int) $lastActivityAt >= $idleMinutes * 60) {
            return $this->expire($request, $guard, 'La sesión expiró por inactividad. Inicia sesión nuevamente.');
        }

        if ($now - (int) $authenticatedAt >= $absoluteMinutes * 60) {
            return $this->expire($request, $guard, 'La sesión alcanzó su duración máxima. Inicia sesión nuevamente.');
        }

        $request->session()->put('security.last_activity_at', $now);

        return $next($request);
    }

    private function expire(Request $request, string $guard, string $message): JsonResponse|RedirectResponse
    {
        Auth::guard('admin')->logout();
        Auth::guard('web')->logout();
        Auth::guard('platform')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        $route = match ($guard) {
            'platform' => 'control.login',
            'admin' => 'admin.login',
            default => 'login',
        };

        return redirect()->route($route)->with('error', $message);
    }

    private function guardPrefix(string $guard): string
    {
        return match ($guard) {
            'platform' => 'platform',
            'admin' => 'admin',
            default => 'employee',
        };
    }
}
