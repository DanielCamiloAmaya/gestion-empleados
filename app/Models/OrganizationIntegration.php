<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class OrganizationIntegration extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'integration_catalog_id', 'status', 'encrypted_config', 'last_synced_at', 'last_error'];

    protected function casts(): array
    {
        return ['encrypted_config' => 'encrypted:array', 'last_synced_at' => 'datetime'];
    }

    public function catalog()
    {
        return $this->belongsTo(IntegrationCatalog::class, 'integration_catalog_id');
    }
}
