<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index()
    {
        $employee = auth()->user()->load([
            'departamento',
            'manager',
            'directReports',
            'leaveRequests' => fn ($query) => $query->latest()->limit(3),
            'onboardingTasks' => fn ($query) => $query->whereNot('status', 'completed')->orderBy('due_date')->limit(4),
            'performanceGoals' => fn ($query) => $query->where('status', 'active')->orderBy('target_date')->limit(4),
        ]);

        return view('home.index', compact('employee'));
    }
}
