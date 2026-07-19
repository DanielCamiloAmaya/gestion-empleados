<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SupportAccessGrant;
use App\Services\PlatformAuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantSupportAccessController extends Controller
{
    public function index()
    {
        return view('security.support-access', [
            'grants' => SupportAccessGrant::with(['platformUser', 'approver'])
                ->where('organization_id', auth('admin')->user()->organization_id)
                ->latest()
                ->get(),
        ]);
    }

    public function review(Request $request, SupportAccessGrant $grant, PlatformAuditService $platformAudit)
    {
        abort_unless($grant->organization_id === auth('admin')->user()->organization_id, 404);
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])]]);
        abort_unless($grant->status === 'pending' && $grant->expires_at->isFuture(), 422, 'La solicitud ya no está disponible.');
        $grant->update([
            'status' => $data['decision'],
            'approved_by_admin_id' => auth('admin')->id(),
            'approved_at' => $data['decision'] === 'approved' ? now() : null,
            'starts_at' => $data['decision'] === 'approved' ? now() : null,
            'expires_at' => $data['decision'] === 'approved' ? now()->addMinutes($grant->duration_minutes) : $grant->expires_at,
        ]);
        $platformAudit->record($request, 'support_access.customer_'.$data['decision'], $grant, [
            'customer_admin_id' => auth('admin')->id(),
            'customer_admin_name' => auth('admin')->user()->name,
            'ticket' => $grant->ticket_reference,
        ], $grant->organization);
        AuditLog::record($request, 'support_access.'.$data['decision'], $grant, [], [
            'ticket' => $grant->ticket_reference,
            'scopes' => $grant->scopes,
            'expires_at' => $grant->expires_at,
        ]);

        return back()->with('success', $data['decision'] === 'approved' ? 'Acceso temporal aprobado.' : 'Solicitud de soporte rechazada.');
    }

    public function revoke(Request $request, SupportAccessGrant $grant, PlatformAuditService $platformAudit)
    {
        abort_unless($grant->organization_id === auth('admin')->user()->organization_id, 404);
        $grant->update(['status' => 'revoked', 'revoked_at' => now()]);
        AuditLog::record($request, 'support_access.revoked_by_customer', $grant);
        $platformAudit->record($request, 'support_access.customer_revoked', $grant, [
            'customer_admin_id' => auth('admin')->id(),
            'customer_admin_name' => auth('admin')->user()->name,
            'ticket' => $grant->ticket_reference,
        ], $grant->organization);

        return back()->with('success', 'Acceso de soporte revocado inmediatamente.');
    }
}
