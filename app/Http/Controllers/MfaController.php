<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MfaController extends Controller
{
    public function challenge()
    {
        abort_unless(auth('admin')->check() || auth()->check(), 403);

        return view('security.mfa-challenge');
    }

    public function verify(Request $request, TotpService $totp)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:32']]);
        [$guard, $actor] = $this->actor();
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
            return back()->withErrors(['code' => 'El codigo no es valido o ya fue utilizado.']);
        }

        $request->session()->regenerate();
        $request->session()->put('mfa.verified_actor', $guard.':'.$actor->getKey());

        return redirect()->intended($guard === 'admin' ? route('admin.home') : route('home'));
    }

    public function settings(Request $request, TotpService $totp)
    {
        [, $actor] = $this->actor();
        $secret = null;
        $uri = null;

        if (! $actor->mfa_enabled) {
            $secret = $request->session()->get('mfa.pending_secret') ?? $totp->generateSecret();
            $request->session()->put('mfa.pending_secret', $secret);
            $uri = $totp->provisioningUri($secret, $actor->email ?? $actor->username);
        }

        return view('security.mfa-settings', compact('actor', 'secret', 'uri'));
    }

    public function enable(Request $request, TotpService $totp)
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        [, $actor] = $this->actor();
        $secret = $request->session()->get('mfa.pending_secret');

        if (! $secret || ! $totp->verify($secret, $data['code'])) {
            return back()->withErrors(['code' => 'El codigo no coincide. Revisa la hora de tu dispositivo e intenta de nuevo.']);
        }

        $plainCodes = $totp->recoveryCodes();
        $actor->forceFill([
            'mfa_enabled' => true,
            'mfa_secret' => $secret,
            'mfa_recovery_codes' => array_map(fn ($code) => Hash::make($code), $plainCodes),
            'mfa_confirmed_at' => now(),
        ])->save();
        $request->session()->forget('mfa.pending_secret');
        $guard = auth('admin')->check() ? 'admin' : 'web';
        $request->session()->put('mfa.verified_actor', $guard.':'.$actor->getKey());
        AuditLog::record($request, 'security.mfa_enabled', $actor, [], ['mfa_enabled' => true]);

        return redirect()->route('mfa.settings')->with('recovery_codes', $plainCodes)->with('success', 'Autenticacion multifactor activada. Guarda los codigos de recuperacion.');
    }

    public function disable(Request $request, TotpService $totp)
    {
        $data = $request->validate([
            'password' => ['required', 'current_password:'.(auth('admin')->check() ? 'admin' : 'web')],
            'code' => ['required', 'digits:6'],
        ]);
        [, $actor] = $this->actor();

        if (! $totp->verify($actor->mfa_secret, $data['code'])) {
            return back()->withErrors(['code' => 'El codigo de autenticacion no es valido.']);
        }

        $actor->forceFill([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_recovery_codes' => null,
            'mfa_confirmed_at' => null,
        ])->save();
        AuditLog::record($request, 'security.mfa_disabled', $actor, ['mfa_enabled' => true], ['mfa_enabled' => false]);

        return back()->with('success', 'Autenticacion multifactor desactivada.');
    }

    private function actor(): array
    {
        if (auth('admin')->check()) {
            return ['admin', auth('admin')->user()];
        }

        return ['web', auth()->user()];
    }
}
