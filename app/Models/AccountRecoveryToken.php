<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountRecoveryToken extends Model
{
    protected $fillable = ['organization_id', 'actor_type', 'actor_id', 'email', 'token_hash', 'expires_at', 'used_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
