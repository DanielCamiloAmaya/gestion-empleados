<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'organization_id', 'plan_id', 'status', 'licensed_seats', 'billing_cycle',
        'external_customer_id', 'external_subscription_id', 'trial_ends_at',
        'current_period_starts_at', 'current_period_ends_at', 'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
