<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalEntity extends Model
{
    protected $fillable = [
        'uuid', 'organization_id', 'legal_name', 'trade_name', 'country_code',
        'tax_id_type', 'tax_identifier', 'registration_number', 'registered_address',
        'is_primary', 'verification_status', 'verified_at', 'verified_by',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'verified_at' => 'datetime'];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
