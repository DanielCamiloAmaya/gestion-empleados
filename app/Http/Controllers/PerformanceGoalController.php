<?php

namespace App\Http\Controllers;

use App\Http\Requests\PerformanceGoalStoreRequest;
use App\Models\AuditLog;
use App\Models\PerformanceGoal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerformanceGoalController extends Controller
{
    public function index()
    {
        $query = PerformanceGoal::with(['employee.departamento', 'creator'])->orderBy('target_date');

        if (! auth('admin')->check()) {
            $query->where('user_id', auth()->id());
        }

        return view('goals.index', ['goals' => $query->paginate(12)]);
    }

    public function create()
    {
        return view('goals.create', [
            'employees' => User::whereIn('employment_status', ['onboarding', 'active'])->orderBy('first_name')->get(),
        ]);
    }

    public function store(PerformanceGoalStoreRequest $request)
    {
        $goal = DB::transaction(function () use ($request) {
            $goal = PerformanceGoal::create([
                ...$request->validated(),
                'created_by' => auth('admin')->id(),
                'status' => 'active',
            ]);
            AuditLog::record($request, 'goal.created', $goal, [], $goal->toArray());

            return $goal;
        });

        return redirect()->route('goals.index')->with('success', "Objetivo “{$goal->title}” activado.");
    }

    public function updateProgress(Request $request, PerformanceGoal $goal)
    {
        if (! auth('admin')->check() && $goal->user_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate(['progress' => ['required', 'integer', 'between:0,100']]);

        DB::transaction(function () use ($request, $goal, $data) {
            $old = $goal->toArray();
            $goal->update([
                'progress' => $data['progress'],
                'status' => $data['progress'] === 100 ? 'completed' : 'active',
            ]);
            AuditLog::record($request, 'goal.progressed', $goal, $old, $goal->fresh()->toArray());
        });

        return back()->with('success', 'Progreso actualizado.');
    }
}
