<?php

namespace App\Http\Controllers;

use App\Models\PlatformUser;
use App\Notifications\PlatformUserInvitationNotification;
use App\Services\AccessLifecycleService;
use App\Services\PlatformAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ControlCenterUserController extends Controller
{
    public function index()
    {
        return view('control-center.users.index', [
            'users' => PlatformUser::orderByRaw("case status when 'active' then 1 when 'invited' then 2 else 3 end")->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, PlatformAuditService $audit)
    {
        $request->merge(['email' => Str::lower((string) $request->input('email'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:platform_users,email'],
            'role' => ['required', Rule::in(array_keys(PlatformUser::PERMISSIONS))],
        ]);
        $plain = Str::random(64);
        $user = PlatformUser::create([
            'uuid' => (string) Str::uuid(),
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'role' => $data['role'],
            'status' => 'invited',
            'invitation_token_hash' => hash('sha256', $plain),
            'invitation_expires_at' => now()->addHours(24),
        ]);
        Notification::route('mail', $user->email)->notify(new PlatformUserInvitationNotification($plain));
        $audit->record($request, 'platform_user.invited', $user, ['role' => $user->role, 'email' => $user->email]);

        $response = back()->with('success', 'Usuario interno invitado. Deberá configurar MFA al activar la cuenta.');
        if (app()->environment('local', 'testing')) {
            $response->with('platform_invitation_url', route('control.invitation.show', $plain));
        }

        return $response;
    }

    public function disable(Request $request, PlatformUser $platformUser, PlatformAuditService $audit, AccessLifecycleService $access)
    {
        abort_if($platformUser->is(auth('platform')->user()), 422, 'No puedes deshabilitar tu propia cuenta.');
        if ($platformUser->role === 'platform_owner' && $platformUser->isActive()) {
            abort_if(PlatformUser::where('role', 'platform_owner')->where('status', 'active')->whereNull('disabled_at')->count() <= 1, 422, 'No puedes deshabilitar al último propietario de plataforma.');
        }
        $platformUser->forceFill(['status' => 'disabled', 'disabled_at' => now()])->save();
        $access->revokePlatformUser($platformUser);
        $audit->record($request, 'platform_user.disabled', $platformUser, ['role' => $platformUser->role]);

        return back()->with('success', 'Cuenta interna deshabilitada y sesiones persistentes invalidadas.');
    }
}
