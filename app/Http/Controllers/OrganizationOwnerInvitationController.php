<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\OrganizationOwnerInvitation;
use App\Models\Role;
use App\Services\PlatformAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class OrganizationOwnerInvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = $this->invitation($token);
        abort_unless($invitation->isAcceptable(), 410, 'La invitación venció, fue revocada o ya se utilizó.');

        return view('control-center.organization-owner-invitation', compact('invitation', 'token'));
    }

    public function accept(Request $request, string $token, PlatformAuditService $audit)
    {
        $invitation = $this->invitation($token);
        abort_unless($invitation->isAcceptable(), 410);
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'terms' => ['accepted'],
        ]);

        $admin = DB::transaction(function () use ($invitation, $data) {
            $lockedInvitation = OrganizationOwnerInvitation::whereKey($invitation->id)->lockForUpdate()->firstOrFail();
            if (! $lockedInvitation->isAcceptable()) {
                throw ValidationException::withMessages(['invitation' => 'La invitación ya no está disponible.']);
            }
            if (Admin::withoutGlobalScopes()->where('organization_id', $lockedInvitation->organization_id)->where('email', $lockedInvitation->email)->exists()) {
                throw ValidationException::withMessages(['email' => 'Ya existe una cuenta administrativa con este correo.']);
            }
            $admin = Admin::create([
                'organization_id' => $lockedInvitation->organization_id,
                'email' => $lockedInvitation->email,
                'name' => $lockedInvitation->name,
                'password' => $data['password'],
                'role' => 'owner',
                'email_verified_at' => now(),
            ]);
            if ($role = Role::withoutGlobalScopes()->where('organization_id', $invitation->organization_id)->where('slug', 'owner')->first()) {
                $admin->roles()->syncWithoutDetaching([$role->id]);
            }
            $lockedInvitation->update(['status' => 'accepted', 'accepted_at' => now()]);

            return $admin;
        });
        $audit->record($request, 'organization_owner.invitation_accepted', $admin, [
            'email' => $invitation->email,
        ], $invitation->organization);

        return redirect()->route('organization-owner-invitations.complete', $token);
    }

    public function complete(string $token)
    {
        $invitation = $this->invitation($token);
        abort_unless($invitation->status === 'accepted' && $invitation->accepted_at, 404);

        return view('control-center.organization-owner-complete', compact('invitation'));
    }

    private function invitation(string $token): OrganizationOwnerInvitation
    {
        return OrganizationOwnerInvitation::with('organization')
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();
    }
}
