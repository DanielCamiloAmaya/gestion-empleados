<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveStoreRequest;
use App\Models\AuditLog;
use App\Models\LeavePolicy;
use App\Models\LeaveRequest;
use App\Services\LeaveBalanceService;
use App\Services\NotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['employee.departamento', 'reviewer'])->latest();

        if (! auth('admin')->check()) {
            $query->where('user_id', auth()->id());
        }

        $query->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')));

        $scope = auth('admin')->check()
            ? LeaveRequest::query()
            : LeaveRequest::where('user_id', auth()->id());

        return view('leave.index', [
            'requests' => $query->paginate(15)->withQueryString(),
            'metrics' => [
                'pending' => (clone $scope)->where('status', 'pending')->count(),
                'approved' => (clone $scope)->where('status', 'approved')->count(),
                'days' => (clone $scope)->where('status', 'approved')->whereYear('start_date', now()->year)->sum('days'),
            ],
        ]);
    }

    public function create(LeaveBalanceService $balances)
    {
        $policies = LeavePolicy::where('is_active', true)->get();

        return view('leave.create', ['balances' => $policies->map(fn ($policy) => $balances->balance(auth()->user(), $policy))->load('policy')]);
    }

    public function store(LeaveStoreRequest $request, NotificationService $notifications)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $data['leave_policy_id'] = LeavePolicy::where('type', $data['type'])->value('id');
        $data['days'] = CarbonImmutable::parse($data['start_date'])->diffInDays(CarbonImmutable::parse($data['end_date'])) + 1;

        $leave = DB::transaction(function () use ($request, $data) {
            $leave = LeaveRequest::create($data);
            AuditLog::record($request, 'leave.requested', $leave, [], $leave->toArray());

            return $leave;
        });

        $notifications->admins('approvals.review', [
            'title' => 'Nueva solicitud de ausencia',
            'body' => auth()->user()->full_name.' solicita '.$leave->days.' dia(s).',
            'url' => route('approvals.index'),
            'category' => 'approval',
        ]);
        $notifications->manager(auth()->user()->manager, [
            'title' => 'Ausencia pendiente de decision',
            'body' => auth()->user()->full_name.' envio una solicitud para revision.',
            'url' => route('approvals.index'),
            'category' => 'approval',
        ]);

        return redirect()->route('leave.index')->with('success', "Solicitud enviada por {$leave->days} día(s).");
    }

    public function review(Request $request, LeaveRequest $leaveRequest, NotificationService $notifications, LeaveBalanceService $balances)
    {
        $isAdminReviewer = auth('admin')->check() && auth('admin')->user()->hasPermission('approvals.review');
        $isManagerReviewer = auth()->check() && $leaveRequest->employee->manager_id === auth()->id();
        abort_unless($isAdminReviewer || $isManagerReviewer, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['nullable', 'required_if:status,rejected', 'string', 'max:1000'],
        ]);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Esta solicitud ya fue procesada y no puede revisarse de nuevo.');
        }

        DB::transaction(function () use ($request, $leaveRequest, $data) {
            $old = $leaveRequest->toArray();
            $leaveRequest->update([
                ...$data,
                'reviewed_by' => auth('admin')->id(),
                'reviewed_by_user_id' => auth('admin')->check() ? null : auth()->id(),
                'reviewed_at' => now(),
            ]);
            AuditLog::record($request, 'leave.'.$data['status'], $leaveRequest, $old, $leaveRequest->fresh()->toArray());
        });

        if ($leaveRequest->policy) {
            $balances->syncUsed($leaveRequest->employee, $leaveRequest->policy, (int) $leaveRequest->start_date->year);
        }

        $notifications->employee($leaveRequest->employee, [
            'title' => $data['status'] === 'approved' ? 'Ausencia aprobada' : 'Ausencia requiere ajustes',
            'body' => $data['status'] === 'approved' ? 'Tu solicitud fue aprobada.' : ($data['review_note'] ?? 'Tu solicitud fue rechazada.'),
            'url' => route('leave.index'),
            'severity' => $data['status'] === 'approved' ? 'success' : 'warning',
            'category' => 'approval',
        ]);

        return back()->with('success', $data['status'] === 'approved' ? 'Solicitud aprobada.' : 'Solicitud rechazada con observación.');
    }
}
