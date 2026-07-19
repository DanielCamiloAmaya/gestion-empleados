<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class CompensationRecord extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'user_id', 'base_salary', 'currency', 'frequency', 'variable_target', 'pay_grade', 'effective_from', 'effective_to', 'created_by'];

    protected function casts(): array
    {
        return ['base_salary' => 'decimal:2', 'variable_target' => 'decimal:2', 'effective_from' => 'date', 'effective_to' => 'date'];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
