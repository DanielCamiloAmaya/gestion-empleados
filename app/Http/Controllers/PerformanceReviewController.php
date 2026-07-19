<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PerformanceReview;
use App\Models\ReviewCycle;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PerformanceReviewController extends Controller
{
    public function index()
    {
        $cycles = ReviewCycle::withCount(['reviews', 'reviews as submitted_count' => fn ($q) => $q->where('status', 'submitted')])->latest()->get();
        $reviews = PerformanceReview::with(['cycle', 'employee', 'adminReviewer'])->when(! auth('admin')->check(), fn ($q) => $q->where('user_id', auth()->id()))->latest()->paginate(15);

        return view('reviews.index', compact('cycles', 'reviews') + ['employees' => auth('admin')->check() ? User::where('employment_status', 'active')->orderBy('first_name')->get() : collect()]);
    }

    public function cycle(Request $request)
    {
        abort_unless(auth('admin')->user()?->hasPermission('reviews.manage'), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'type' => ['required', Rule::in(['manager', 'probation', 'annual'])], 'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at']]);
        $cycle = ReviewCycle::create([...$data, 'created_by' => auth('admin')->id(), 'status' => 'active']);
        foreach (User::where('employment_status', 'active')->get() as $employee) {
            PerformanceReview::create(['review_cycle_id' => $cycle->id, 'user_id' => $employee->id, 'reviewer_admin_id' => auth('admin')->id()]);
        }

        return back()->with('success', 'Ciclo activado para '.User::where('employment_status', 'active')->count().' personas.');
    }

    public function submit(Request $request, PerformanceReview $review, NotificationService $notifications)
    {
        abort_unless(auth('admin')->user()?->hasPermission('reviews.manage'), 403);
        $data = $request->validate(['performance_score' => ['required', 'integer', 'between:1,5'], 'potential_score' => ['required', 'integer', 'between:1,5'], 'summary' => ['required', 'string', 'min:20', 'max:4000'], 'strengths' => ['required', 'string', 'min:10', 'max:3000'], 'development_areas' => ['required', 'string', 'min:10', 'max:3000']]);
        DB::transaction(function () use ($request, $review, $data) {
            $old = $review->toArray();
            $review->update([...$data, 'status' => 'submitted', 'submitted_at' => now(), 'reviewer_admin_id' => auth('admin')->id()]);
            AuditLog::record($request, 'review.submitted', $review, $old, $review->fresh()->toArray());
        });
        $notifications->employee($review->employee, ['title' => 'Evaluacion disponible', 'body' => $review->cycle->name.' ya puede ser consultada y reconocida.', 'url' => route('reviews.index'), 'category' => 'performance']);

        return back()->with('success', 'Evaluacion publicada para el empleado.');
    }

    public function acknowledge(Request $request, PerformanceReview $review)
    {
        abort_unless(auth()->check() && $review->user_id === auth()->id() && $review->status === 'submitted', 403);
        $review->update(['status' => 'acknowledged', 'acknowledged_at' => now()]);
        AuditLog::record($request, 'review.acknowledged', $review, ['status' => 'submitted'], ['status' => 'acknowledged']);

        return back()->with('success', 'Lectura y conversación de seguimiento confirmadas.');
    }
}
