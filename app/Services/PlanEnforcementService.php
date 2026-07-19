<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PlanEnforcementService
{
    public function allowsFeature(Organization $organization, string $feature): bool
    {
        $organization->loadMissing('subscription.plan');
        $subscription = $organization->subscription;

        if (! $subscription || ! in_array($subscription->status, ['trialing', 'active'], true)) {
            return false;
        }

        $features = $subscription->plan?->features ?? [];

        return in_array('all', $features, true) || in_array($feature, $features, true);
    }

    public function assertFeature(Organization $organization, string $feature): void
    {
        if (! $this->allowsFeature($organization, $feature)) {
            throw ValidationException::withMessages([
                'plan' => "La función {$feature} no está incluida en el plan activo de la empresa.",
            ]);
        }
    }

    public function assertCanAddEmployees(Organization $organization, int $additional = 1): void
    {
        $subscription = $organization->subscription;
        if (! $subscription || ! in_array($subscription->status, ['trialing', 'active'], true)) {
            throw ValidationException::withMessages([
                'subscription' => 'La suscripción no está activa. Reactívala antes de incorporar personas.',
            ]);
        }

        $limit = min(
            (int) $organization->seat_limit,
            (int) $subscription->licensed_seats,
            (int) ($subscription->plan?->limits['employees'] ?? PHP_INT_MAX),
        );
        $occupied = User::withoutGlobalScope('organization')
            ->where('organization_id', $organization->id)
            ->whereIn('employment_status', ['active', 'onboarding', 'leave'])
            ->count();

        if ($occupied + $additional > $limit) {
            throw ValidationException::withMessages([
                'seat_limit' => "El plan permite {$limit} puestos y actualmente hay {$occupied} ocupados. Amplía la suscripción para continuar.",
            ]);
        }
    }
}
