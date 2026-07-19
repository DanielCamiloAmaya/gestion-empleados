<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\LegalEntity;
use App\Models\Organization;
use App\Models\OrganizationDomain;
use App\Models\OrganizationOwnerInvitation;
use App\Models\Plan;
use App\Models\PlatformUser;
use App\Models\SupportAccessGrant;
use App\Models\User;
use App\Notifications\OrganizationOwnerInvitationNotification;
use App\Services\OrganizationDomainVerifier;
use App\Services\PlatformAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ControlCenterOperationsController extends Controller
{
    public function legalEntity(Request $request, Organization $organization, PlatformAuditService $audit)
    {
        $organization->loadMissing('subscription.plan');
        $limit = (int) ($organization->subscription?->plan?->limits['legal_entities'] ?? 1);
        abort_if($organization->legalEntities()->count() >= $limit, 422, "El plan permite hasta {$limit} entidades legales.");
        $data = $request->validate([
            'legal_name' => ['required', 'string', 'max:200'],
            'trade_name' => ['nullable', 'string', 'max:200'],
            'country_code' => ['required', 'string', 'size:2'],
            'tax_id_type' => ['required', Rule::in(['NIT', 'RUT', 'RFC', 'EIN', 'VAT', 'OTHER'])],
            'tax_identifier' => ['required', 'string', 'max:80'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'registered_address' => ['nullable', 'string', 'max:1000'],
        ]);
        if (LegalEntity::where('country_code', Str::upper($data['country_code']))
            ->where('tax_id_type', $data['tax_id_type'])
            ->where('tax_identifier', $data['tax_identifier'])
            ->exists()) {
            return back()->withInput()->withErrors(['tax_identifier' => 'Esta identificación fiscal ya está registrada.']);
        }
        $entity = $organization->legalEntities()->create($data + [
            'uuid' => (string) Str::uuid(),
            'country_code' => Str::upper($data['country_code']),
            'is_primary' => false,
            'verification_status' => 'pending',
        ]);
        $audit->record($request, 'legal_entity.created', $entity, [
            'legal_name' => $entity->legal_name,
            'tax_id_type' => $entity->tax_id_type,
            'tax_identifier_suffix' => Str::substr($entity->tax_identifier, -4),
        ], $organization);

        return back()->with('success', 'Entidad legal agregada y pendiente de verificación documental.');
    }

    public function verifyLegalEntity(Request $request, LegalEntity $legalEntity, PlatformAuditService $audit)
    {
        $legalEntity->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => auth('platform')->id(),
        ]);
        $audit->record($request, 'legal_entity.verified', $legalEntity, [], $legalEntity->organization);

        return back()->with('success', 'Entidad legal marcada como verificada.');
    }

    public function domain(Request $request, Organization $organization, PlatformAuditService $audit)
    {
        $organization->loadMissing('subscription.plan');
        $limit = (int) ($organization->subscription?->plan?->limits['domains'] ?? 1);
        abort_if($organization->domains()->count() >= $limit, 422, "El plan permite hasta {$limit} dominios.");
        $data = $request->validate(['domain' => ['required', 'string', 'max:253']]);
        $domainName = Str::lower(trim(preg_replace('#^https?://#', '', $data['domain']), " \t\n\r\0\x0B/"));
        if (! filter_var($domainName, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || ! str_contains($domainName, '.')) {
            return back()->withErrors(['domain' => 'Ingresa un dominio raíz válido, por ejemplo empresa.com.']);
        }
        $request->merge(['normalized_domain' => $domainName]);
        $request->validate(['normalized_domain' => ['unique:organization_domains,domain']]);

        $domain = $organization->domains()->create([
            'domain' => $domainName,
            'verification_token' => 'pos_domain_'.Str::random(40),
            'verification_status' => 'pending',
        ]);
        $audit->record($request, 'organization_domain.created', $domain, ['domain' => $domainName], $organization);

        return back()->with('success', 'Dominio agregado. Publica el registro TXT mostrado para verificarlo.');
    }

    public function verifyDomain(Request $request, OrganizationDomain $domain, OrganizationDomainVerifier $verifier, PlatformAuditService $audit)
    {
        if (! $verifier->verify($domain)) {
            return back()->withErrors(['domain' => 'El registro TXT aún no coincide. La propagación DNS puede tardar varias horas.']);
        }
        $domain->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => auth('platform')->id(),
        ]);
        $audit->record($request, 'organization_domain.verified', $domain, ['domain' => $domain->domain], $domain->organization);

        return back()->with('success', 'Dominio corporativo verificado.');
    }

    public function subscription(Request $request, Organization $organization, PlatformAuditService $audit)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'licensed_seats' => ['required', 'integer', 'between:1,100000'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'annual', 'contract'])],
            'status' => ['required', Rule::in(['trialing', 'active', 'past_due', 'paused', 'canceled'])],
        ]);
        $plan = Plan::findOrFail($data['plan_id']);
        $maximumSeats = (int) ($plan->limits['employees'] ?? PHP_INT_MAX);
        $occupiedSeats = User::withoutGlobalScope('organization')
            ->where('organization_id', $organization->id)
            ->whereIn('employment_status', ['active', 'onboarding', 'leave'])
            ->count();
        if ((int) $data['licensed_seats'] > $maximumSeats || (int) $data['licensed_seats'] < $occupiedSeats) {
            throw ValidationException::withMessages([
                'licensed_seats' => "El contrato debe quedar entre {$occupiedSeats} puestos ocupados y {$maximumSeats} permitidos por el plan.",
            ]);
        }
        $old = $organization->subscription?->only(['plan_id', 'licensed_seats', 'billing_cycle', 'status']);
        $organization->subscription()->updateOrCreate([], $data);
        $organization->update(['plan' => $plan->code, 'seat_limit' => $data['licensed_seats']]);
        $billingBlocked = in_array($data['status'], ['past_due', 'paused', 'canceled'], true);
        if ($billingBlocked) {
            $organization->update([
                'is_active' => false,
                'lifecycle_status' => 'suspended',
                'suspended_at' => now(),
                'suspension_reason' => 'Suspensión automática por estado de suscripción: '.$data['status'],
            ]);
        } elseif (str_starts_with((string) $organization->suspension_reason, 'Suspensión automática por estado de suscripción:')) {
            $organization->update([
                'is_active' => true,
                'lifecycle_status' => 'active',
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);
        }
        $audit->record($request, 'subscription.updated', $organization->subscription, [
            'before' => $old, 'after' => $data,
        ], $organization);

        return back()->with('success', 'Suscripción y límites actualizados.');
    }

    public function inviteOwner(Request $request, Organization $organization, PlatformAuditService $audit)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);
        $email = Str::lower($data['email']);
        if (Admin::withoutGlobalScopes()->where('organization_id', $organization->id)->where('email', $email)->exists()) {
            return back()->withErrors(['email' => 'Esta persona ya tiene una cuenta administrativa. Los cambios de propietario se realizan desde el gobierno de la empresa.']);
        }
        $plain = Str::random(64);
        $invitation = $organization->ownerInvitations()->create([
            'name' => $data['name'],
            'email' => $email,
            'token_hash' => hash('sha256', $plain),
            'status' => 'pending',
            'expires_at' => now()->addHours(72),
            'invited_by' => auth('platform')->id(),
        ]);
        Notification::route('mail', $invitation->email)
            ->notify(new OrganizationOwnerInvitationNotification($organization, $plain));
        $audit->record($request, 'organization_owner.invited', $invitation, ['email' => $invitation->email], $organization);

        $response = back()->with('success', 'Invitación de propietario enviada.');
        if (app()->environment('local', 'testing')) {
            $response->with('owner_invitation_url', route('organization-owner-invitations.show', $plain));
        }

        return $response;
    }

    public function revokeInvitation(Request $request, OrganizationOwnerInvitation $invitation, PlatformAuditService $audit)
    {
        abort_if($invitation->accepted_at, 422, 'Una invitación aceptada no puede revocarse.');
        $invitation->update(['status' => 'revoked', 'revoked_at' => now()]);
        $audit->record($request, 'organization_owner.invitation_revoked', $invitation, [], $invitation->organization);

        return back()->with('success', 'Invitación revocada.');
    }

    public function supportGrant(Request $request, Organization $organization, PlatformAuditService $audit)
    {
        $data = $request->validate([
            'platform_user_id' => ['required', 'exists:platform_users,id'],
            'ticket_reference' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'min:20', 'max:2000'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => [Rule::in(['organization.read', 'configuration.read', 'audit.read', 'integration.diagnostics'])],
            'duration_minutes' => ['required', 'integer', Rule::in([15, 30, 60, 120, 240])],
        ]);
        $supportUser = PlatformUser::where('id', $data['platform_user_id'])->whereIn('role', ['platform_owner', 'support', 'security'])->where('status', 'active')->firstOrFail();
        $grant = $organization->supportAccessGrants()->create([
            'uuid' => (string) Str::uuid(),
            'platform_user_id' => $supportUser->id,
            'ticket_reference' => $data['ticket_reference'],
            'reason' => $data['reason'],
            'scopes' => $data['scopes'],
            'status' => 'pending',
            'duration_minutes' => $data['duration_minutes'],
            'expires_at' => now()->addHours(24),
        ]);
        $audit->record($request, 'support_access.requested', $grant, [
            'support_user' => $supportUser->email,
            'ticket' => $grant->ticket_reference,
            'scopes' => $grant->scopes,
            'expires_at' => $grant->expires_at->toIso8601String(),
        ], $organization);

        return back()->with('success', 'Acceso solicitado. Un propietario de la empresa debe aprobarlo antes del vencimiento.');
    }

    public function revokeSupportGrant(Request $request, SupportAccessGrant $grant, PlatformAuditService $audit)
    {
        $grant->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by_platform_user_id' => auth('platform')->id(),
        ]);
        $audit->record($request, 'support_access.revoked', $grant, ['ticket' => $grant->ticket_reference], $grant->organization);

        return back()->with('success', 'Acceso de soporte revocado inmediatamente.');
    }

    public function supportSession(Request $request, SupportAccessGrant $grant, PlatformAuditService $audit)
    {
        abort_unless($grant->platform_user_id === auth('platform')->id(), 403, 'El acceso fue asignado a otro especialista.');
        abort_unless($grant->isUsable(), 410, 'El acceso no está aprobado, fue revocado o ya venció.');
        abort_unless(collect($grant->scopes)->every(fn (string $scope) => str_ends_with($scope, '.read') || $scope === 'integration.diagnostics'), 403);

        $grant->forceFill(['last_accessed_at' => now()])->save();
        $organization = $grant->organization()->with([
            'subscription.plan', 'legalEntities', 'domains',
        ])->withCount(['admins', 'employees'])->firstOrFail();
        $audit->record($request, 'support_access.opened', $grant, [
            'ticket' => $grant->ticket_reference,
            'scopes' => $grant->scopes,
            'expires_at' => $grant->expires_at->toIso8601String(),
        ], $organization);

        return view('control-center.support-session', compact('grant', 'organization'));
    }
}
