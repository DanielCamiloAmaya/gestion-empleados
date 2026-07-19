<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'review_cycle_id', 'user_id', 'reviewer_admin_id', 'reviewer_user_id', 'performance_score', 'potential_score', 'summary', 'strengths', 'development_areas', 'status', 'submitted_at', 'acknowledged_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'acknowledged_at' => 'datetime'];
    }

    public function cycle()
    {
        return $this->belongsTo(ReviewCycle::class, 'review_cycle_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adminReviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewer_admin_id');
    }
}
