<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;

class TenantAccessProvisioner
{
    public function provision(Organization $organization): void
    {
        $owner = Role::withoutGlobalScopes()->firstOrCreate(
            ['organization_id' => $organization->id, 'slug' => 'owner'],
            [
                'name' => 'Propietario',
                'description' => 'Control total y no delegable de la organización.',
                'is_system' => true,
            ],
        );
        $hr = Role::withoutGlobalScopes()->firstOrCreate(
            ['organization_id' => $organization->id, 'slug' => 'hr-admin'],
            [
                'name' => 'RR. HH.',
                'description' => 'Operación de personas sin configuración crítica de plataforma.',
                'is_system' => true,
            ],
        );
        $owner->permissions()->sync(Permission::pluck('id'));
        $hr->permissions()->sync(Permission::whereNotIn('slug', ['security.manage', 'integrations.manage'])->pluck('id'));
    }
}
