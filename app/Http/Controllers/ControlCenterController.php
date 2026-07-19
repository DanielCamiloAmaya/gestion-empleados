<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\PlatformAuditLog;
use App\Models\SupportAccessGrant;
use App\Services\PlatformAuditService;
use Illuminate\Http\Request;

class ControlCenterController extends Controller
{
    public function dashboard(Request $request, PlatformAuditService $audit)
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');
        $organizations = Organization::query()
            ->with(['subscription.plan', 'legalEntities', 'domains'])
            ->withCount(['admins', 'employees'])
            ->when($search, fn ($query) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('legal_name', 'like', "%{$search}%")
                ->orWhere('tax_identifier', 'like', "%{$search}%")))
            ->when(in_array($status, ['onboarding', 'active', 'suspended', 'offboarded'], true), fn ($query) => $query->where('lifecycle_status', $status))
            ->orderByRaw("case lifecycle_status when 'onboarding' then 1 when 'suspended' then 2 when 'active' then 3 else 4 end")
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('control-center.dashboard', [
            'organizations' => $organizations,
            'counts' => Organization::query()->selectRaw('lifecycle_status, count(*) total')->groupBy('lifecycle_status')->pluck('total', 'lifecycle_status'),
            'pendingSupport' => SupportAccessGrant::where('status', 'pending')->count(),
            'recentAudit' => PlatformAuditLog::with(['actor', 'organization'])->latest('id')->limit(8)->get(),
            'auditChainValid' => $audit->verifyChain(),
        ]);
    }

    public function audit(PlatformAuditService $audit)
    {
        abort_unless(auth('platform')->user()->hasPermission('audit.view'), 403);

        return view('control-center.audit', [
            'events' => PlatformAuditLog::with(['actor', 'organization'])->latest('id')->paginate(50),
            'chainValid' => $audit->verifyChain(),
        ]);
    }
}
