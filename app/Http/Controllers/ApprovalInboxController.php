<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\OnboardingSubmission;

class ApprovalInboxController extends Controller
{
    public function index()
    {
        $admin = auth('admin')->user();
        $user = auth()->user();

        $leaves = LeaveRequest::with('employee.departamento')
            ->where('status', 'pending')
            ->when(! $admin, fn ($query) => $query->whereHas('employee', fn ($employee) => $employee->where('manager_id', $user->id)))
            ->orderBy('created_at')
            ->get();

        $submissions = OnboardingSubmission::with(['task.employee.departamento', 'files'])
            ->where('status', 'submitted')
            ->whereHas('task', function ($query) use ($admin, $user) {
                $query->when(! $admin, fn ($tasks) => $tasks->whereHas('employee', fn ($employees) => $employees->where('manager_id', $user->id)));
            })
            ->orderBy('submitted_at')
            ->get();

        return view('approvals.index', [
            'leaves' => $leaves,
            'submissions' => $submissions,
            'total' => $leaves->count() + $submissions->count(),
            'oldestAt' => collect([$leaves->min('created_at'), $submissions->min('submitted_at')])->filter()->min(),
        ]);
    }
}
