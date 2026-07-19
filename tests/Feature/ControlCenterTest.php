<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\LegalEntity;
use App\Models\Organization;
use App\Models\OrganizationDomain;
use App\Models\OrganizationOwnerInvitation;
use App\Models\Plan;
use App\Models\PlatformUser;
use App\Models\Subscription;
use App\Models\SupportAccessGrant;
use App\Notifications\OrganizationOwnerInvitationNotification;
use App\Services\PlatformAuditService;
use App\Services\TenantAccessProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ControlCenterTest extends TestCase
{
    use RefreshDatabase;

    private PlatformUser $platformOwner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->platformOwner = PlatformUser::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Platform Owner',
            'email' => 'platform.owner@peopleos.test',
            'password' => 'Control-Secure-2026!',
            'role' => 'platform_owner',
            'status' => 'active',
            'mfa_enabled' => true,
            'mfa_secret' => 'JBSWY3DPEHPK3PXP',
            'mfa_confirmed_at' => now(),
            'activated_at' => now(),
        ]);
    }

    public function test_control_center_has_independent_authentication_and_requires_mfa(): void
    {
        $this->get(route('control.dashboard'))->assertRedirect(route('control.login'));

        $this->post(route('control.login.store'), [
            'email' => $this->platformOwner->email,
            'password' => 'Control-Secure-2026!',
        ])->assertRedirect(route('control.mfa.challenge'));

        $this->get(route('control.dashboard'))->assertRedirect(route('control.mfa.challenge'));
        $this->assertAuthenticatedAs($this->platformOwner, 'platform');
        $this->assertGuest('admin');
        $this->assertGuest('web');
    }

    public function test_platform_owner_provisions_company_legal_identity_subscription_roles_and_invitation(): void
    {
        Notification::fake();
        $plan = Plan::where('code', 'business')->firstOrFail();

        $this->asPlatform($this->platformOwner)->post(route('control.organizations.store'), [
            'name' => 'Andina Logistics',
            'slug' => 'andina-logistics',
            'legal_name' => 'Andina Logistics S.A.S.',
            'country_code' => 'CO',
            'tax_id_type' => 'NIT',
            'tax_identifier' => '901777222-1',
            'registered_address' => 'Carrera 7 # 90-10, Bogotá',
            'timezone' => 'America/Bogota',
            'locale' => 'es',
            'plan_id' => $plan->id,
            'licensed_seats' => 420,
            'billing_cycle' => 'annual',
            'owner_name' => 'Laura Directora',
            'owner_email' => 'laura@andina.example',
        ])->assertRedirect();

        $organization = Organization::where('slug', 'andina-logistics')->firstOrFail();
        $this->assertSame('onboarding', $organization->lifecycle_status);
        $this->assertFalse($organization->is_active);
        $this->assertTrue($organization->settings['require_admin_mfa']);
        $this->assertDatabaseHas('legal_entities', [
            'organization_id' => $organization->id,
            'tax_identifier' => '901777222-1',
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'licensed_seats' => 420,
            'status' => 'trialing',
        ]);
        $this->assertDatabaseHas('roles', ['organization_id' => $organization->id, 'slug' => 'owner']);
        $this->assertDatabaseHas('roles', ['organization_id' => $organization->id, 'slug' => 'hr-admin']);
        $this->assertDatabaseHas('organization_owner_invitations', [
            'organization_id' => $organization->id,
            'email' => 'laura@andina.example',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('platform_audit_logs', [
            'organization_id' => $organization->id,
            'event' => 'organization.created',
        ]);
        Notification::assertSentOnDemand(OrganizationOwnerInvitationNotification::class);
    }

    public function test_owner_invitation_is_single_use_and_never_contains_a_predefined_password(): void
    {
        $organization = $this->onboardingOrganization();
        $plain = 'owner-'.Str::random(48);
        OrganizationOwnerInvitation::create([
            'organization_id' => $organization->id,
            'name' => 'Owner Customer',
            'email' => 'owner@customer.example',
            'token_hash' => hash('sha256', $plain),
            'status' => 'pending',
            'expires_at' => now()->addHours(2),
            'invited_by' => $this->platformOwner->id,
        ]);

        $this->get(route('organization-owner-invitations.show', $plain))
            ->assertOk()
            ->assertSee('Crearás tu propia contraseña');
        $this->post(route('organization-owner-invitations.accept', $plain), [
            'password' => 'Customer-Owner-2026!',
            'password_confirmation' => 'Customer-Owner-2026!',
            'terms' => '1',
        ])->assertRedirect(route('organization-owner-invitations.complete', $plain));

        $admin = Admin::withoutGlobalScopes()->where('organization_id', $organization->id)->where('email', 'owner@customer.example')->firstOrFail();
        $this->assertTrue(Hash::check('Customer-Owner-2026!', $admin->password));
        $this->assertSame('owner', $admin->role);
        $this->assertSame('accepted', OrganizationOwnerInvitation::first()->status);
        $this->get(route('organization-owner-invitations.show', $plain))->assertStatus(410);
    }

    public function test_activation_gate_requires_legal_domain_owner_and_subscription_controls(): void
    {
        $organization = $this->onboardingOrganization();

        $this->asPlatform($this->platformOwner)->patch(route('control.organizations.transition', $organization), [
            'status' => 'active',
        ])->assertSessionHasErrors('status');

        LegalEntity::create([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'legal_name' => 'Controlled Customer S.A.S.',
            'country_code' => 'CO',
            'tax_id_type' => 'NIT',
            'tax_identifier' => '901888333-2',
            'is_primary' => true,
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $this->platformOwner->id,
        ]);
        OrganizationDomain::create([
            'organization_id' => $organization->id,
            'domain' => 'controlled.example',
            'verification_token' => 'pos_domain_test',
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $this->platformOwner->id,
        ]);
        Admin::create([
            'organization_id' => $organization->id,
            'name' => 'Customer Owner',
            'email' => 'owner@controlled.example',
            'password' => 'Customer-Owner-2026!',
            'role' => 'owner',
        ]);

        $this->asPlatform($this->platformOwner)->patch(route('control.organizations.transition', $organization), [
            'status' => 'active',
        ])->assertSessionHas('success');
        $this->assertTrue($organization->fresh()->is_active);
        $this->assertSame('active', $organization->fresh()->lifecycle_status);
    }

    public function test_support_access_needs_customer_approval_is_time_limited_and_only_assignee_can_open_it(): void
    {
        $organization = Organization::where('slug', 'peopleos-demo')->firstOrFail();
        $tenantOwner = Admin::create([
            'organization_id' => $organization->id,
            'name' => 'Tenant Owner',
            'email' => 'tenant.owner@peopleos.test',
            'password' => 'Tenant-Owner-2026!',
            'role' => 'owner',
        ]);
        $support = PlatformUser::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Support Specialist',
            'email' => 'support@peopleos.test',
            'password' => 'Support-Secure-2026!',
            'role' => 'support',
            'status' => 'active',
            'mfa_enabled' => true,
            'mfa_secret' => 'JBSWY3DPEHPK3PXP',
            'mfa_confirmed_at' => now(),
            'activated_at' => now(),
        ]);
        $grant = SupportAccessGrant::create([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'platform_user_id' => $support->id,
            'ticket_reference' => 'SUP-1001',
            'reason' => 'Diagnóstico de configuración solicitado expresamente por el cliente.',
            'scopes' => ['organization.read', 'configuration.read'],
            'status' => 'pending',
            'duration_minutes' => 30,
            'expires_at' => now()->addDay(),
        ]);

        $this->asPlatform($support)->get(route('control.support.session', $grant))->assertStatus(410);
        $this->actingAs($tenantOwner, 'admin')->patch(route('support-access.review', $grant), [
            'decision' => 'approved',
        ])->assertSessionHas('success');

        $grant->refresh();
        $this->assertTrue($grant->isUsable());
        $this->assertTrue($grant->expires_at->between(now()->addMinutes(29), now()->addMinutes(31)));
        $this->asPlatform($support)->get(route('control.support.session', $grant))
            ->assertOk()
            ->assertSee('No se muestran expedientes');
        $this->asPlatform($this->platformOwner)->get(route('control.support.session', $grant))->assertForbidden();
        $this->assertDatabaseHas('platform_audit_logs', ['event' => 'support_access.customer_approved']);
        $this->assertDatabaseHas('platform_audit_logs', ['event' => 'support_access.opened']);
    }

    public function test_internal_roles_cannot_exceed_their_declared_permissions(): void
    {
        $support = PlatformUser::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Restricted Support',
            'email' => 'restricted.support@peopleos.test',
            'password' => 'Support-Secure-2026!',
            'role' => 'support',
            'status' => 'active',
            'mfa_enabled' => true,
            'mfa_secret' => 'JBSWY3DPEHPK3PXP',
            'mfa_confirmed_at' => now(),
            'activated_at' => now(),
        ]);

        $this->asPlatform($support)->get(route('control.organizations.create'))->assertForbidden();
        $this->asPlatform($support)->get(route('control.audit'))->assertForbidden();
        $this->asPlatform($support)->get(route('control.dashboard'))->assertOk();
    }

    public function test_platform_audit_chain_is_immutable_and_detects_database_tampering(): void
    {
        $request = Request::create('/control-center', 'POST');
        $request->attributes->set('request_id', (string) Str::uuid());
        $this->actingAs($this->platformOwner, 'platform');
        $audit = app(PlatformAuditService::class);
        $entry = $audit->record($request, 'control.tested', 'ControlCenter', ['result' => 'ok']);

        $this->assertTrue($audit->verifyChain());
        $this->expectException(\LogicException::class);
        $entry->update(['actor_name' => 'Altered']);
    }

    public function test_platform_audit_chain_reports_raw_tampering(): void
    {
        $request = Request::create('/control-center', 'POST');
        $this->actingAs($this->platformOwner, 'platform');
        $audit = app(PlatformAuditService::class);
        $entry = $audit->record($request, 'control.tested', 'ControlCenter', ['result' => 'ok']);
        DB::table('platform_audit_logs')->where('id', $entry->id)->update(['metadata' => json_encode(['result' => 'altered'])]);

        $this->assertFalse($audit->verifyChain());
    }

    private function asPlatform(PlatformUser $user): static
    {
        return $this->actingAs($user, 'platform')->withSession([
            'mfa.verified_actor' => 'platform:'.$user->id,
            'security.guard' => 'platform',
            'security.version' => (int) config('session_security.version'),
            'security.authenticated_at' => now()->timestamp,
            'security.last_activity_at' => now()->timestamp,
        ]);
    }

    private function onboardingOrganization(): Organization
    {
        $organization = Organization::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Controlled Customer',
            'slug' => 'controlled-customer-'.Str::lower(Str::random(5)),
            'legal_name' => 'Controlled Customer S.A.S.',
            'country_code' => 'CO',
            'timezone' => 'America/Bogota',
            'locale' => 'es',
            'plan' => 'business',
            'is_active' => false,
            'lifecycle_status' => 'onboarding',
            'seat_limit' => 100,
            'settings' => ['require_admin_mfa' => true],
        ]);
        Subscription::create([
            'organization_id' => $organization->id,
            'plan_id' => Plan::where('code', 'business')->value('id'),
            'status' => 'trialing',
            'licensed_seats' => 100,
            'billing_cycle' => 'annual',
            'trial_ends_at' => now()->addMonth(),
        ]);
        app(TenantAccessProvisioner::class)->provision($organization);

        return $organization;
    }
}
