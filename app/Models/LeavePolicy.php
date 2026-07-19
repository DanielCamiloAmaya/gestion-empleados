<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class LeavePolicy extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'name', 'type', 'annual_allowance', 'carryover_max', 'minimum_notice_days', 'maximum_consecutive_days', 'is_active'];

    protected function casts(): array
    {
        return ['annual_allowance' => 'decimal:2', 'carryover_max' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function balances()
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
