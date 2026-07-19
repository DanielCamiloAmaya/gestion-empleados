<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportAccessGrant extends Model
{
    protected $fillable = [
        'uuid', 'organization_id', 'platform_user_id', 'ticket_reference', 'reason',
        'scopes', 'status', 'starts_at', 'expires_at', 'approved_by_admin_id',
        'approved_at', 'revoked_at', 'revoked_by_platform_user_id', 'duration_minutes',
        'last_accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function platformUser()
    {
        return $this->belongsTo(PlatformUser::class);
    }

    public function approver()
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function isUsable(): bool
    {
        return $this->status === 'approved'
            && $this->approved_at !== null
            && $this->revoked_at === null
            && $this->expires_at->isFuture()
            && ($this->starts_at === null || $this->starts_at->isPast());
    }
}
