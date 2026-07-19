<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'uuid', 'name', 'slug', 'legal_name', 'tax_identifier', 'country_code',
        'timezone', 'locale', 'plan', 'is_active', 'settings', 'lifecycle_status',
        'activated_at', 'suspended_at', 'suspension_reason', 'onboarding_completed_at',
        'seat_limit',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    public function admins()
    {
        return $this->hasMany(Admin::class);
    }

    public function employees()
    {
        return $this->hasMany(User::class);
    }

    public function legalEntities()
    {
        return $this->hasMany(LegalEntity::class);
    }

    public function domains()
    {
        return $this->hasMany(OrganizationDomain::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function ownerInvitations()
    {
        return $this->hasMany(OrganizationOwnerInvitation::class);
    }

    public function supportAccessGrants()
    {
        return $this->hasMany(SupportAccessGrant::class);
    }
}
