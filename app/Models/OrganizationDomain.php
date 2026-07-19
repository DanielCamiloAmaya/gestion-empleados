<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationDomain extends Model
{
    protected $fillable = [
        'organization_id', 'domain', 'verification_token', 'verification_status',
        'verified_at', 'verified_by',
    ];

    protected $hidden = ['verification_token'];

    protected function casts(): array
    {
        return ['verification_token' => 'encrypted', 'verified_at' => 'datetime'];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function dnsRecordName(): string
    {
        return '_peopleos-verification.'.$this->domain;
    }

    public function dnsRecordValue(): string
    {
        return 'peopleos-verification='.$this->verification_token;
    }
}
