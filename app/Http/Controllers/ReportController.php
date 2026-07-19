<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\LeaveRequest;
use App\Models\OffboardingCase;
use App\Models\PerformanceReview;
use App\Models\User;

class ReportController extends Controller
{
    public function index()
    {
        $headcount = User::whereIn('employment_status', ['active', 'onboarding', 'leave'])->count();

        return view('reports.index', ['metrics' => ['headcount' => $headcount, 'new_hires' => User::whereDate('hire_date', '>=', now()->subDays(90))->count(), 'approved_leave_days' => LeaveRequest::where('status', 'approved')->whereYear('start_date', now()->year)->sum('days'), 'open_offboarding' => OffboardingCase::where('status', 'open')->count(), 'review_completion' => ($total = PerformanceReview::count()) ? round(PerformanceReview::whereIn('status', ['submitted', 'acknowledged'])->count() / $total * 100) : 0], 'departments' => Departamento::withCount(['empleados' => fn ($q) => $q->whereIn('employment_status', ['active', 'onboarding', 'leave'])])->orderByDesc('empleados_count')->get(), 'statusBreakdown' => User::selectRaw('employment_status, count(*) total')->groupBy('employment_status')->pluck('total', 'employment_status')]);
    }
}
