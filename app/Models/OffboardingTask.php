<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OffboardingTask extends Model
{
    protected $fillable = ['offboarding_case_id', 'title', 'category', 'due_date', 'status', 'completed_at'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'completed_at' => 'datetime'];
    }

    public function case()
    {
        return $this->belongsTo(OffboardingCase::class, 'offboarding_case_id');
    }
}
