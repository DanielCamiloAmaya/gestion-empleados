<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class WebhookEndpoint extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'name', 'url', 'secret', 'events', 'is_active', 'failure_count', 'last_delivered_at'];

    protected function casts(): array
    {
        return ['secret' => 'encrypted', 'events' => 'array', 'is_active' => 'boolean', 'last_delivered_at' => 'datetime'];
    }
}
