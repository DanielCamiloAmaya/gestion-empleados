<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EmployeeInvitation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class InvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = $this->invitation($token);

        return view('auth.invitation', compact('invitation', 'token'));
    }

    public function accept(Request $request, string $token)
    {
        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()]]);
        $invitation = $this->invitation($token);
        $invitation->employee->update(['password' => $data['password']]);
        $invitation->update(['accepted_at' => now()]);
        AuditLog::record($request, 'employee.invitation_accepted', $invitation->employee, [], ['invitation_accepted' => true]);

        return redirect()->route('login', ['workspace' => $invitation->organization->slug])->with('success', 'Cuenta activada. Ya puedes iniciar sesion.');
    }

    private function invitation(string $token): EmployeeInvitation
    {
        return EmployeeInvitation::with(['employee', 'organization'])
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();
    }
}
