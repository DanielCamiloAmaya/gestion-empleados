<?php

namespace App\Http\Controllers;

use App\Models\LegalEntity;
use App\Models\Organization;
use App\Models\OrganizationOwnerInvitation;
use App\Models\Plan;
use App\Models\PlatformUser;
use App\Models\Subscription;
use App\Notifications\OrganizationOwnerInvitationNotification;
use App\Services\OrganizationLifecycleService;
use App\Services\PlatformAuditService;
use App\Services\TenantAccessProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ControlCenterOrganizationController extends Controller
{
    public function create()
    {
        return view('control-center.organizations.create', ['plans' => Plan::where('is_active', true)->get()]);
    }

    public function store(Request $request, PlatformAuditService $audit, TenantAccessProvisioner $accessProvisioner)
    {
        $request->merge([
            'slug' => Str::lower((string) $request->input('slug')),
            'owner_email' => Str::lower((string) $request->input('owner_email')),
            'country_code' => Str::upper((string) $request->input('country_code')),
        ]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:80', 'unique:organizations,slug'],
            'legal_name' => ['required', 'string', 'max:200'],
            'country_code' => ['required', 'string', 'size:2'],
            'tax_id_type' => ['required', Rule::in(['NIT', 'RUT', 'RFC', 'EIN', 'VAT', 'OTHER'])],
            'tax_identifier' => ['required', 'string', 'max:80'],
            'registered_address' => ['nullable', 'string', 'max:1000'],
            'timezone' => ['required', 'timezone:all'],
            'locale' => ['required', Rule::in(['es', 'en', 'pt'])],
            'plan_id' => ['required', 'exists:plans,id'],
            'licensed_seats' => ['required', 'integer', 'between:1,100000'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'annual', 'contract'])],
            'owner_name' => ['required', 'string', 'max:160'],
            'owner_email' => ['required', 'email:rfc', 'max:255'],
        ]);
        if (LegalEntity::where('country_code', $data['country_code'])
            ->where('tax_id_type', $data['tax_id_type'])
            ->where('tax_identifier', $data['tax_identifier'])
            ->exists()) {
            return back()->withInput()->withErrors(['tax_identifier' => 'Esta identificación fiscal ya pertenece a otra empresa.']);
        }

        $plan = Plan::findOrFail($data['plan_id']);
        $maximumSeats = (int) ($plan->limits['employees'] ?? PHP_INT_MAX);
        if ((int) $data['licensed_seats'] > $maximumSeats) {
            throw ValidationException::withMessages([
                'licensed_seats' => "El plan {$plan->name} admite hasta {$maximumSeats} puestos.",
            ]);
        }

        [$organization, $plainToken, $invitation] = DB::transaction(function () use ($data, $accessProvisioner, $plan) {
            $organization = Organization::create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'slug' => Str::lower($data['slug']),
                'legal_name' => $data['legal_name'],
                'tax_identifier' => $data['tax_identifier'],
                'country_code' => Str::upper($data['country_code']),
                'timezone' => $data['timezone'],
                'locale' => $data['locale'],
                'plan' => $plan->code,
                'is_active' => false,
                'lifecycle_status' => 'onboarding',
                'seat_limit' => $data['licensed_seats'],
                'settings' => ['created_via' => 'control_center', 'require_admin_mfa' => true, 'require_employee_mfa' => true],
            ]);
            LegalEntity::create([
                'uuid' => (string) Str::uuid(),
                'organization_id' => $organization->id,
                'legal_name' => $data['legal_name'],
                'trade_name' => $data['name'],
                'country_code' => Str::upper($data['country_code']),
                'tax_id_type' => $data['tax_id_type'],
                'tax_identifier' => $data['tax_identifier'],
                'registered_address' => $data['registered_address'] ?? null,
                'is_primary' => true,
                'verification_status' => 'pending',
            ]);
            Subscription::create([
                'organization_id' => $organization->id,
                'plan_id' => $plan->id,
                'status' => 'trialing',
                'licensed_seats' => $data['licensed_seats'],
                'billing_cycle' => $data['billing_cycle'],
                'trial_ends_at' => now()->addDays(30),
                'current_period_starts_at' => now(),
                'current_period_ends_at' => $data['billing_cycle'] === 'monthly' ? now()->addMonth() : now()->addYear(),
            ]);
            $accessProvisioner->provision($organization);
            $plainToken = Str::random(64);
            $invitation = OrganizationOwnerInvitation::create([
                'organization_id' => $organization->id,
                'name' => $data['owner_name'],
                'email' => Str::lower($data['owner_email']),
                'token_hash' => hash('sha256', $plainToken),
                'status' => 'pending',
                'expires_at' => now()->addHours(72),
                'invited_by' => auth('platform')->id(),
            ]);

            return [$organization, $plainToken, $invitation];
        });

        Notification::route('mail', $invitation->email)
            ->notify(new OrganizationOwnerInvitationNotification($organization, $plainToken));
        $audit->record($request, 'organization.created', $organization, [
            'slug' => $organization->slug,
            'plan' => $organization->plan,
            'owner_email' => $invitation->email,
        ], $organization);

        $redirect = redirect()->route('control.organizations.show', $organization)
            ->with('success', 'Empresa creada en onboarding. La invitación del propietario fue enviada.');
        if (app()->environment('local', 'testing')) {
            $redirect->with('owner_invitation_url', route('organization-owner-invitations.show', $plainToken));
        }

        return $redirect;
    }

    public function show(Organization $organization)
    {
        $organization->load([
            'legalEntities', 'domains', 'subscription.plan',
            'ownerInvitations' => fn ($query) => $query->latest(),
            'supportAccessGrants.platformUser', 'supportAccessGrants.approver',
        ])->loadCount(['admins', 'employees']);

        return view('control-center.organizations.show', [
            'organization' => $organization,
            'plans' => Plan::where('is_active', true)->get(),
            'supportUsers' => PlatformUser::where('status', 'active')
                ->whereIn('role', ['platform_owner', 'support', 'security'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function transition(Request $request, Organization $organization, OrganizationLifecycleService $lifecycle, PlatformAuditService $audit)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended', 'offboarded'])],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $old = $organization->lifecycle_status;
        $lifecycle->transition($organization, $data['status'], $data['reason'] ?? null);
        $audit->record($request, 'organization.lifecycle_changed', $organization, [
            'from' => $old, 'to' => $data['status'], 'reason' => $data['reason'] ?? null,
        ], $organization);

        return back()->with('success', "Estado actualizado de {$old} a {$data['status']}.");
    }
}
