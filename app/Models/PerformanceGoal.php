<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceGoal extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id', 'user_id', 'created_by', 'title', 'description', 'target_date', 'progress', 'status',
    ];

    protected function casts(): array
    {
        return ['target_date' => 'date', 'progress' => 'integer'];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
