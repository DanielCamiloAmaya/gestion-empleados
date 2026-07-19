<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class ComplianceControl extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'framework', 'control_code', 'title', 'evidence', 'status', 'owner_id', 'next_review_at', 'verified_by', 'verified_at', 'review_note'];

    protected function casts(): array
    {
        return ['next_review_at' => 'date', 'verified_at' => 'datetime'];
    }
}
