<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'job_posting_id', 'name', 'email', 'phone', 'stage', 'score', 'notes'];

    public function job()
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }
}
