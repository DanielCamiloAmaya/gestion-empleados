<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationOwnerInvitation extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'email', 'token_hash', 'status', 'expires_at',
        'accepted_at', 'revoked_at', 'invited_by',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'accepted_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function isAcceptable(): bool
    {
        return $this->status === 'pending'
            && $this->revoked_at === null
            && $this->accepted_at === null
            && $this->expires_at->isFuture();
    }
}
