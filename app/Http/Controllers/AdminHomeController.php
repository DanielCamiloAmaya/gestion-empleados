<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Departamento;
use App\Models\LeaveRequest;
use App\Models\OnboardingTask;
use App\Models\PerformanceGoal;
use App\Models\User;

class AdminHomeController extends Controller
{
    public function index()
    {
        $headcount = User::whereIn('employment_status', ['active', 'onboarding', 'leave'])->count();
        $active = User::where('employment_status', 'active')->count();

        return view('admin.home', [
            'metrics' => [
                'headcount' => $headcount,
                'active' => $active,
                'onboarding' => User::where('employment_status', 'onboarding')->count(),
                'leave' => User::where('employment_status', 'leave')->count(),
                'departments' => Departamento::where('is_active', true)->count(),
            ],
            'activeRate' => $headcount > 0 ? (int) round(($active / $headcount) * 100) : 0,
            'recentEmployees' => User::with('departamento')->latest()->limit(5)->get(),
            'departmentStats' => Departamento::withCount(['empleados' => fn ($query) => $query->where('employment_status', 'active')])
                ->where('is_active', true)->orderByDesc('empleados_count')->limit(6)->get(),
            'recentActivity' => AuditLog::latest('created_at')->limit(6)->get(),
            'operations' => [
                'pendingLeave' => LeaveRequest::where('status', 'pending')->count(),
                'overdueTasks' => OnboardingTask::whereNot('status', 'completed')->whereDate('due_date', '<', today())->count(),
                'activeGoals' => PerformanceGoal::where('status', 'active')->count(),
            ],
        ]);
    }
}
