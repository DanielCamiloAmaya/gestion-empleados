<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'title', 'provider', 'description', 'duration_minutes', 'is_mandatory', 'status'];

    protected function casts(): array
    {
        return ['is_mandatory' => 'boolean'];
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }
}
