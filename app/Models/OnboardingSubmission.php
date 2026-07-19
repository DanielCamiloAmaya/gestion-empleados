<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'onboarding_task_id', 'submitted_by', 'version', 'message', 'status', 'submitted_at',
        'reviewed_by_admin_id', 'reviewed_by_user_id', 'reviewed_at', 'review_note',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function task()
    {
        return $this->belongsTo(OnboardingTask::class, 'onboarding_task_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function files()
    {
        return $this->hasMany(OnboardingDeliverableFile::class);
    }

    public function adminReviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }

    public function managerReviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function getReviewerNameAttribute(): ?string
    {
        return $this->adminReviewer?->name ?? $this->managerReviewer?->full_name;
    }
}
