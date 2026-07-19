<?php

namespace App\Http\Controllers;

use App\Http\Requests\OnboardingTaskStoreRequest;
use App\Models\AuditLog;
use App\Models\OnboardingTask;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OnboardingTaskController extends Controller
{
    public function index(Request $request)
    {
        $query = OnboardingTask::with(['employee.departamento', 'employee.manager', 'creator', 'latestSubmission.files'])
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'pending' THEN 2 ELSE 3 END")
            ->orderBy('due_date');

        if (! auth('admin')->check()) {
            $query->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhereHas('employee', fn ($employee) => $employee->where('manager_id', auth()->id()));
            });
        }

        $query->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')));

        return view('onboarding.index', ['tasks' => $query->paginate(15)->withQueryString()]);
    }

    public function show(OnboardingTask $task)
    {
        $task->load([
            'employee.departamento',
            'employee.manager',
            'creator',
            'submissions.files',
            'submissions.submitter',
            'submissions.adminReviewer',
            'submissions.managerReviewer',
        ]);

        $isOwner = auth()->check() && $task->user_id === auth()->id();
        $canReview = auth('admin')->check()
            || (auth()->check() && $task->employee->manager_id === auth()->id());

        abort_unless(auth('admin')->check() || $isOwner || $canReview, 403);

        return view('onboarding.show', [
            'task' => $task,
            'isOwner' => $isOwner,
            'canReview' => $canReview,
            'hasPendingReview' => $task->submissions->contains('status', 'submitted'),
        ]);
    }

    public function create()
    {
        return view('onboarding.create', [
            'employees' => User::whereIn('employment_status', ['onboarding', 'active'])->orderBy('first_name')->get(),
        ]);
    }

    public function store(OnboardingTaskStoreRequest $request, NotificationService $notifications)
    {
        $task = DB::transaction(function () use ($request) {
            $task = OnboardingTask::create([
                ...$request->validated(),
                'created_by' => auth('admin')->id(),
            ]);
            AuditLog::record($request, 'onboarding.created', $task, [], $task->toArray());

            return $task;
        });

        $notifications->employee($task->employee, [
            'title' => 'Nueva tarea asignada',
            'body' => $task->title,
            'url' => route('onboarding.show', $task),
            'category' => 'onboarding',
        ]);

        return redirect()->route('onboarding.index')->with('success', "Tarea “{$task->title}” asignada.");
    }

    public function updateStatus(Request $request, OnboardingTask $task)
    {
        abort_unless(auth('admin')->check(), 403);

        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'in_progress', 'completed'])]]);

        if ($data['status'] === 'completed' && ! $task->submissions()->where('status', 'approved')->exists()) {
            return back()->with('error', 'La tarea solo puede completarse después de aprobar un entregable.');
        }

        DB::transaction(function () use ($request, $task, $data) {
            $old = $task->toArray();
            $task->update([
                'status' => $data['status'],
                'completed_at' => $data['status'] === 'completed' ? now() : null,
            ]);
            AuditLog::record($request, 'onboarding.updated', $task, $old, $task->fresh()->toArray());
        });

        return back()->with('success', 'Estado de la tarea actualizado.');
    }
}
