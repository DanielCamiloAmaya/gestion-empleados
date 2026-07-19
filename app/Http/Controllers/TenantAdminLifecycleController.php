<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AdminInvitation;
use App\Models\AuditLog;
use App\Notifications\SecureActionLinkNotification;
use App\Services\AccessLifecycleService;
use App\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TenantAdminLifecycleController extends Controller
{
    public function invite(Request $request)
    {
        abort_unless(auth('admin')->user()->hasPermission('security.manage'), 403);
        $organization = app(OrganizationContext::class)->organization();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'role_id' => ['required', Rule::exists('roles', 'id')->where(fn ($query) => $query->where('organization_id', $organization->id))],
        ]);
        $email = Str::lower($data['email']);
        abort_if(Admin::where('email', $email)->exists(), 422, 'Ya existe una cuenta administrativa con este correo.');
        AdminInvitation::where('organization_id', $organization->id)->where('email', $email)->whereNull('accepted_at')->update(['revoked_at' => now()]);

        $plain = Str::random(64);
        $invitation = AdminInvitation::create([
            ...$data,
            'organization_id' => $organization->id,
            'invited_by' => auth('admin')->id(),
            'email' => $email,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addHours(48),
        ]);
        $url = route('admin-invitations.show', ['token' => $plain, 'workspace' => $organization->slug]);
        Notification::route('mail', $email)->notify(new SecureActionLinkNotification(
            'Invitación para administrar '.$organization->name,
            auth('admin')->user()->name.' te invitó a administrar '.$organization->name.' con permisos controlados.',
            'Activar cuenta administrativa',
            $url,
            'en 48 horas',
        ));
        AuditLog::record($request, 'security.admin_invited', $invitation, [], ['email' => $email, 'role_id' => $data['role_id']]);

        $response = back()->with('success', 'Invitación administrativa enviada.');
        if (app()->environment('local', 'testing')) {
            $response->with('admin_invitation_url', $url);
        }

        return $response;
    }

    public function show(string $token)
    {
        $invitation = $this->validInvitation($token);

        return view('auth.admin-invitation', compact('invitation', 'token'));
    }

    public function accept(Request $request, string $token)
    {
        $invitation = $this->validInvitation($token);
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        DB::transaction(function () use ($invitation, $data) {
            $admin = Admin::create([
                'organization_id' => $invitation->organization_id,
                'name' => $invitation->name,
                'email' => $invitation->email,
                'password' => $data['password'],
                'role' => $invitation->role?->slug === 'owner' ? 'owner' : 'custom',
                'status' => 'active',
            ]);
            if ($invitation->role_id) {
                $admin->roles()->sync([$invitation->role_id]);
            }
            $invitation->update(['accepted_at' => now()]);
        });

        return redirect()->route('admin.login', ['workspace' => $invitation->organization->slug])->with('success', 'Cuenta activada. Ya puedes iniciar sesión.');
    }

    public function disable(Request $request, Admin $admin, AccessLifecycleService $access)
    {
        abort_unless(auth('admin')->user()->hasPermission('security.manage'), 403);
        abort_if($admin->is(auth('admin')->user()), 422, 'No puedes deshabilitar tu propia cuenta.');
        abort_if($admin->role === 'owner' && Admin::where('role', 'owner')->where('status', 'active')->count() <= 1, 422, 'No puedes deshabilitar al último propietario.');

        $admin->update(['status' => 'disabled', 'disabled_at' => now()]);
        $access->revokeAdmin($admin);
        AuditLog::record($request, 'security.admin_disabled', $admin, ['status' => 'active'], ['status' => 'disabled']);

        return back()->with('success', 'Administrador deshabilitado; sus credenciales y tokens fueron revocados.');
    }

    public function enable(Request $request, Admin $admin)
    {
        abort_unless(auth('admin')->user()->hasPermission('security.manage'), 403);
        $admin->update(['status' => 'active', 'disabled_at' => null, 'auth_version' => $admin->auth_version + 1]);
        AuditLog::record($request, 'security.admin_enabled', $admin, ['status' => 'disabled'], ['status' => 'active']);

        return back()->with('success', 'Administrador reactivado. Deberá autenticarse nuevamente.');
    }

    private function validInvitation(string $token): AdminInvitation
    {
        $invitation = AdminInvitation::with(['organization', 'role'])->where('token_hash', hash('sha256', $token))->firstOrFail();
        abort_if($invitation->accepted_at || $invitation->revoked_at || $invitation->expires_at->isPast() || ! $invitation->organization->is_active, 410, 'La invitación ya no es válida.');

        return $invitation;
    }
}
