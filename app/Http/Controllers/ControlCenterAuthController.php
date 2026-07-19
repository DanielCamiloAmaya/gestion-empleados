<?php

namespace App\Http\Controllers;

use App\Models\PlatformUser;
use App\Services\PlatformAuditService;
use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ControlCenterAuthController extends Controller
{
    public function showLogin()
    {
        if (auth('platform')->check()) {
            return redirect()->route('control.dashboard');
        }

        return view('control-center.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        Auth::guard('platform')->logout();
        Auth::guard('admin')->logout();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (! Auth::guard('platform')->attempt([
            'email' => mb_strtolower($data['email']),
            'password' => $data['password'],
            'status' => 'active',
        ], false)) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Las credenciales internas no son válidas o la cuenta está deshabilitada.',
            ]);
        }

        $request->session()->regenerate();
        $actor = auth('platform')->user();
        $request->session()->put([
            'security.guard' => 'platform',
            'security.version' => (int) config('session_security.version'),
            'security.authenticated_at' => now()->timestamp,
            'security.last_activity_at' => now()->timestamp,
            'security.auth_version' => (int) ($actor->auth_version ?? 1),
        ]);
        $actor->forceFill(['last_login_at' => now()])->save();
        $request->session()->forget('mfa.verified_actor');

        return redirect()->route($actor->mfa_enabled ? 'control.mfa.challenge' : 'control.mfa.enroll');
    }

    public function showEnrollment(Request $request, TotpService $totp)
    {
        $actor = auth('platform')->user();
        abort_unless($actor && ! $actor->mfa_enabled, 404);
        $secret = $request->session()->get('platform.mfa.pending_secret') ?? $totp->generateSecret();
        $request->session()->put('platform.mfa.pending_secret', $secret);
        $uri = $totp->provisioningUri($secret, $actor->email, 'PeopleOS Control Center');

        return view('control-center.auth.mfa-enroll', compact('actor', 'secret', 'uri'));
    }

    public function enableMfa(Request $request, TotpService $totp, PlatformAuditService $audit)
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $actor = auth('platform')->user();
        $secret = $request->session()->get('platform.mfa.pending_secret');
        if (! $secret || ! $totp->verify($secret, $data['code'])) {
            return back()->withErrors(['code' => 'El código no coincide. Verifica la hora del dispositivo.']);
        }

        $plainCodes = $totp->recoveryCodes();
        $actor->forceFill([
            'mfa_enabled' => true,
            'mfa_secret' => $secret,
            'mfa_recovery_codes' => array_map(fn (string $code) => Hash::make($code), $plainCodes),
            'mfa_confirmed_at' => now(),
        ])->save();
        $request->session()->forget('platform.mfa.pending_secret');
        $request->session()->put('mfa.verified_actor', 'platform:'.$actor->getKey());
        $audit->record($request, 'platform_user.mfa_enabled', $actor, ['role' => $actor->role]);

        return redirect()->route('control.dashboard')->with('recovery_codes', $plainCodes)
            ->with('success', 'MFA activado. Guarda los códigos de recuperación antes de continuar.');
    }

    public function showChallenge()
    {
        abort_unless(auth('platform')->user()?->mfa_enabled, 403);

        return view('control-center.auth.mfa-challenge');
    }

    public function verifyMfa(Request $request, TotpService $totp, PlatformAuditService $audit)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:32']]);
        $actor = auth('platform')->user();
        abort_unless($actor?->mfa_enabled, 403);

        $verified = $totp->verify($actor->mfa_secret, $data['code']);
        if (! $verified) {
            $codes = $actor->mfa_recovery_codes ?? [];
            foreach ($codes as $index => $hash) {
                if (Hash::check(strtoupper(trim($data['code'])), $hash)) {
                    unset($codes[$index]);
                    $actor->forceFill(['mfa_recovery_codes' => array_values($codes)])->save();
                    $verified = true;
                    break;
                }
            }
        }

        if (! $verified) {
            return back()->withErrors(['code' => 'El código no es válido o ya fue utilizado.']);
        }

        $request->session()->regenerate();
        $request->session()->put('mfa.verified_actor', 'platform:'.$actor->getKey());
        $audit->record($request, 'platform_user.login_verified', $actor);

        return redirect()->intended(route('control.dashboard'));
    }

    public function logout(Request $request, PlatformAuditService $audit)
    {
        if ($actor = auth('platform')->user()) {
            $audit->record($request, 'platform_user.logout', $actor);
        }
        Auth::guard('platform')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('control.login')->with('success', 'Sesión interna cerrada.');
    }

    public function showInvitation(string $token)
    {
        $user = PlatformUser::where('invitation_token_hash', hash('sha256', $token))->firstOrFail();
        abort_unless($user->status === 'invited' && $user->invitation_expires_at?->isFuture(), 410, 'La invitación venció o ya fue utilizada.');

        return view('control-center.auth.invitation', compact('user', 'token'));
    }

    public function acceptInvitation(Request $request, string $token, PlatformAuditService $audit)
    {
        $user = PlatformUser::where('invitation_token_hash', hash('sha256', $token))->firstOrFail();
        abort_unless($user->status === 'invited' && $user->invitation_expires_at?->isFuture(), 410);
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        $user->forceFill([
            'password' => $data['password'],
            'status' => 'active',
            'activated_at' => now(),
            'invitation_token_hash' => null,
            'invitation_expires_at' => null,
        ])->save();
        $audit->record($request, 'platform_user.invitation_accepted', $user, ['role' => $user->role]);

        return redirect()->route('control.login')->with('success', 'Cuenta interna activada. Inicia sesión para configurar MFA.');
    }
}
