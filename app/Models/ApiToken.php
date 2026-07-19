<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'created_by', 'name', 'token_prefix', 'token_hash', 'abilities', 'last_used_at', 'expires_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['abilities' => 'array', 'last_used_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function can(string $ability): bool
    {
        return in_array('*', $this->abilities ?? [], true) || in_array($ability, $this->abilities ?? [], true);
    }
}
