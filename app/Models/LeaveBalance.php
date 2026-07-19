<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'user_id', 'leave_policy_id', 'year', 'allocated', 'carried_over', 'adjustment', 'used'];

    protected function casts(): array
    {
        return ['allocated' => 'decimal:2', 'carried_over' => 'decimal:2', 'adjustment' => 'decimal:2', 'used' => 'decimal:2'];
    }

    public function policy()
    {
        return $this->belongsTo(LeavePolicy::class, 'leave_policy_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getAvailableAttribute(): float
    {
        return (float) $this->allocated + (float) $this->carried_over + (float) $this->adjustment - (float) $this->used;
    }
}
