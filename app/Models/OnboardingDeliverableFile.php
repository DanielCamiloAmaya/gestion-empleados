<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingDeliverableFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'onboarding_submission_id', 'original_name', 'storage_path', 'mime_type',
        'extension', 'size_bytes', 'sha256',
    ];

    protected $hidden = ['storage_path'];

    public function submission()
    {
        return $this->belongsTo(OnboardingSubmission::class, 'onboarding_submission_id');
    }

    public function getHumanSizeAttribute(): string
    {
        if ($this->size_bytes >= 1_048_576) {
            return number_format($this->size_bytes / 1_048_576, 1).' MB';
        }

        return number_format($this->size_bytes / 1024, 0).' KB';
    }
}
