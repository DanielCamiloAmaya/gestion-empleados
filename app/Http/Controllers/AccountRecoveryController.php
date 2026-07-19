<?php

namespace App\Http\Controllers;

use App\Models\AccountRecoveryToken;
use App\Models\Admin;
use App\Models\User;
use App\Notifications\SecureActionLinkNotification;
use App\Services\AccessLifecycleService;
use App\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AccountRecoveryController extends Controller
{
    public function requestForm(string $actor)
    {
        abort_unless(in_array($actor, ['employee', 'admin'], true), 404);

        return view('auth.recovery-request', compact('actor'));
    }

    public function send(Request $request, string $actor)
    {
        abort_unless(in_array($actor, ['employee', 'admin'], true), 404);
        $data = $request->validate(['email' => ['required', 'email:rfc']]);
        $organization = app(OrganizationContext::class)->organization();
        $model = $actor === 'admin'
            ? Admin::where('email', Str::lower($data['email']))->where('status', 'active')->first()
            : User::where('email', Str::lower($data['email']))->whereIn('employment_status', ['active', 'onboarding'])->first();

        if ($model) {
            AccountRecoveryToken::where('actor_type', $actor)->where('actor_id', $model->id)->whereNull('used_at')->delete();
            $plain = Str::random(64);
            AccountRecoveryToken::create([
                'organization_id' => $organization->id,
                'actor_type' => $actor,
                'actor_id' => $model->id,
                'email' => $model->email,
                'token_hash' => hash('sha256', $plain),
                'expires_at' => now()->addMinutes(30),
            ]);
            $url = route('recovery.reset', ['actor' => $actor, 'token' => $plain, 'workspace' => $organization->slug]);
            Notification::route('mail', $model->email)->notify(new SecureActionLinkNotification(
                'Recupera tu acceso a PeopleOS',
                'Recibimos una solicitud para establecer una nueva contraseña.',
                'Crear nueva contraseña',
                $url,
                'en 30 minutos',
            ));
            if (app()->environment('local', 'testing')) {
                $request->session()->flash('recovery_url', $url);
            }
        }

        return back()->with('success', 'Si el correo corresponde a una cuenta activa, enviaremos un enlace de recuperación.');
    }

    public function resetForm(string $actor, string $token)
    {
        $recovery = $this->validToken($actor, $token);

        return view('auth.recovery-reset', compact('actor', 'token', 'recovery'));
    }

    public function reset(Request $request, string $actor, string $token, AccessLifecycleService $access)
    {
        $recovery = $this->validToken($actor, $token);
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);
        $model = $actor === 'admin' ? Admin::findOrFail($recovery->actor_id) : User::findOrFail($recovery->actor_id);
        $model->update(['password' => $data['password']]);
        $actor === 'admin' ? $access->revokeAdmin($model) : $access->revokeEmployee($model);
        $recovery->update(['used_at' => now()]);

        return redirect()->route($actor === 'admin' ? 'admin.login' : 'login', ['workspace' => $recovery->organization->slug])
            ->with('success', 'Contraseña actualizada. Todas las sesiones anteriores fueron cerradas.');
    }

    private function validToken(string $actor, string $token): AccountRecoveryToken
    {
        $recovery = AccountRecoveryToken::with('organization')->where('actor_type', $actor)->where('token_hash', hash('sha256', $token))->firstOrFail();
        abort_if($recovery->used_at || $recovery->expires_at->isPast() || ! $recovery->organization->is_active, 410, 'El enlace de recuperación venció o ya fue utilizado.');

        return $recovery;
    }
}
