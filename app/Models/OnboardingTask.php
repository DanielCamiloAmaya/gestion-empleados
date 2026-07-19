<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingTask extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id', 'user_id', 'created_by', 'title', 'description', 'due_date', 'priority',
        'status', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'completed_at' => 'datetime'];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function submissions()
    {
        return $this->hasMany(OnboardingSubmission::class)->orderByDesc('version');
    }

    public function latestSubmission()
    {
        return $this->hasOne(OnboardingSubmission::class)->ofMany('version', 'max');
    }
}
