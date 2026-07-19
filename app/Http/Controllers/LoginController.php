<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        Auth::guard('admin')->logout();

        $organization = app(OrganizationContext::class)->organization();
        if (! Auth::attempt([...$request->getCredentials(), 'organization_id' => $organization?->id], false)) {
            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Las credenciales no son válidas.',
            ]);
        }

        if (! in_array(Auth::user()->employment_status, ['active', 'onboarding'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['username' => 'Tu cuenta no está activa. Contacta a Recursos Humanos.']);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'security.guard' => 'web',
            'security.version' => (int) config('session_security.version'),
            'security.authenticated_at' => now()->timestamp,
            'security.last_activity_at' => now()->timestamp,
            'security.auth_version' => (int) Auth::user()->auth_version,
            'organization.id' => $organization?->id,
            'organization.slug' => $organization?->slug,
        ]);

        if (Auth::user()->mfa_enabled) {
            $request->session()->forget('mfa.verified_actor');

            return redirect()->route('mfa.challenge');
        }

        return redirect()->intended(route('home'));
    }
}
