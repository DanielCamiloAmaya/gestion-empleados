<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Validation\ValidationException;

class OrganizationLifecycleService
{
    private const TRANSITIONS = [
        'onboarding' => ['active', 'suspended'],
        'active' => ['suspended'],
        'suspended' => ['active', 'offboarded'],
        'offboarded' => [],
    ];

    public function transition(Organization $organization, string $status, ?string $reason = null): void
    {
        if (! in_array($status, self::TRANSITIONS[$organization->lifecycle_status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "No es posible pasar de {$organization->lifecycle_status} a {$status}.",
            ]);
        }

        if ($status === 'suspended' && blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'Debes registrar el motivo de la suspensión.']);
        }

        if ($status === 'active' && $organization->lifecycle_status === 'onboarding') {
            $organization->loadMissing(['legalEntities', 'domains', 'ownerInvitations', 'subscription']);
            $checks = [
                'una entidad legal verificada' => $organization->legalEntities->contains('verification_status', 'verified'),
                'un dominio corporativo verificado' => $organization->domains->contains('verification_status', 'verified'),
                'un propietario activado' => $organization->admins()->where('role', 'owner')->exists(),
                'una suscripción vigente' => $organization->subscription && ! in_array($organization->subscription->status, ['canceled', 'past_due'], true),
            ];
            $missing = collect($checks)->filter(fn (bool $passed) => ! $passed)->keys();
            if ($missing->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'status' => 'Antes de activar completa: '.$missing->join(', ', ' y ').'.',
                ]);
            }
        }

        $organization->forceFill([
            'lifecycle_status' => $status,
            'is_active' => $status === 'active',
            'activated_at' => $status === 'active' ? ($organization->activated_at ?? now()) : $organization->activated_at,
            'suspended_at' => $status === 'suspended' ? now() : null,
            'suspension_reason' => $status === 'suspended' ? $reason : null,
            'onboarding_completed_at' => $status === 'active' ? ($organization->onboarding_completed_at ?? now()) : $organization->onboarding_completed_at,
        ])->save();

        if ($organization->subscription) {
            $organization->subscription->update([
                'status' => $status === 'active' ? 'active' : ($status === 'suspended' ? 'paused' : 'canceled'),
                'canceled_at' => $status === 'offboarded' ? now() : null,
            ]);
        }
    }
}
