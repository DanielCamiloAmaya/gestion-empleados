<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationCatalog extends Model
{
    protected $table = 'integration_catalog';

    protected $fillable = ['slug', 'name', 'category', 'description', 'auth_type', 'is_active', 'capabilities'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'capabilities' => 'array'];
    }
}
