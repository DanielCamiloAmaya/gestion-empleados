<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class CourseEnrollment extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'course_id', 'user_id', 'due_date', 'status', 'score', 'completed_at'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'completed_at' => 'datetime'];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
