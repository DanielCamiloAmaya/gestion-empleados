<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class JobPosting extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'department_id', 'hiring_manager_id', 'title', 'location', 'employment_type', 'description', 'status', 'closes_at'];

    protected function casts(): array
    {
        return ['closes_at' => 'date'];
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function department()
    {
        return $this->belongsTo(Departamento::class, 'department_id');
    }
}
