<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'currency', 'monthly_price_cents',
        'annual_price_cents', 'included_seats', 'limits', 'features', 'is_active',
    ];

    protected function casts(): array
    {
        return ['limits' => 'array', 'features' => 'array', 'is_active' => 'boolean'];
    }
}
