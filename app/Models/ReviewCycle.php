<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class ReviewCycle extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'created_by', 'name', 'type', 'starts_at', 'ends_at', 'status'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date'];
    }

    public function reviews()
    {
        return $this->hasMany(PerformanceReview::class);
    }
}
