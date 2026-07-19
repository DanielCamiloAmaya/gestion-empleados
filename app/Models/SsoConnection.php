<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class SsoConnection extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'name', 'protocol', 'issuer_url', 'client_id', 'client_secret', 'authorization_endpoint', 'token_endpoint', 'jwks_uri', 'allowed_domains', 'is_enabled', 'verified_at'];

    protected function casts(): array
    {
        return ['client_secret' => 'encrypted', 'allowed_domains' => 'array', 'is_enabled' => 'boolean', 'verified_at' => 'datetime'];
    }
}
