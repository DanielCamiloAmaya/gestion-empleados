<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminInvitation extends Model
{
    protected $fillable = ['organization_id', 'role_id', 'invited_by', 'name', 'email', 'token_hash', 'expires_at', 'accepted_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'accepted_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
