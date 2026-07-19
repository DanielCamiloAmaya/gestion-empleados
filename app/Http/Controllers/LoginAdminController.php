<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginAdminRequest;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\Auth;

class LoginAdminController extends Controller
{
    public function showAdmin()
    {
        return view('admin.login');
    }

    public function loginAdmin(LoginAdminRequest $request)
    {
        Auth::guard('admin')->logout();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $organization = app(OrganizationContext::class)->organization();
        if (! Auth::guard('admin')->attempt([...$request->getCredentials(), 'organization_id' => $organization?->id], false)) {
            return back()->withInput($request->only('name'))->withErrors([
                'name' => 'Las credenciales no son válidas.',
            ]);
        }
        if ((Auth::guard('admin')->user()->status ?? 'active') !== 'active') {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['name' => 'La cuenta administrativa está deshabilitada.']);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'security.guard' => 'admin',
            'security.version' => (int) config('session_security.version'),
            'security.authenticated_at' => now()->timestamp,
            'security.last_activity_at' => now()->timestamp,
            'security.auth_version' => (int) (Auth::guard('admin')->user()->auth_version ?? 1),
            'organization.id' => $organization?->id,
            'organization.slug' => $organization?->slug,
        ]);
        $admin = Auth::guard('admin')->user();
        $admin->forceFill(['last_login_at' => now()])->save();

        if ($admin->mfa_enabled) {
            $request->session()->forget('mfa.verified_actor');

            return redirect()->route('mfa.challenge');
        }

        return redirect()->intended(route('admin.home'));
    }
}
