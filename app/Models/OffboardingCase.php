<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class OffboardingCase extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'user_id', 'owner_id', 'last_day', 'reason', 'risk_level', 'notes', 'status', 'completed_at'];

    protected function casts(): array
    {
        return ['last_day' => 'date', 'completed_at' => 'datetime'];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function owner()
    {
        return $this->belongsTo(Admin::class, 'owner_id');
    }

    public function tasks()
    {
        return $this->hasMany(OffboardingTask::class);
    }
}
