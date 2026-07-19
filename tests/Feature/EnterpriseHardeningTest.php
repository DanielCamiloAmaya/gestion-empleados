<?php

namespace Tests\Feature;

use App\Models\AccountRecoveryToken;
use App\Models\Admin;
use App\Models\ApiToken;
use App\Models\Departamento;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use App\Services\AccessLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnterpriseHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_password_recovery_is_single_use_and_revokes_previous_sessions(): void
    {
        $employee = User::factory()->create(['password' => 'Previous-Password1!']);
        $plain = Str::random(64);
        $recovery = AccountRecoveryToken::create([
            'organization_id' => $employee->organization_id,
            'actor_type' => 'employee',
            'actor_id' => $employee->id,
            'email' => $employee->email,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(30),
        ]);
        $previousVersion = $employee->auth_version;

        $this->post(route('recovery.update', [
            'actor' => 'employee',
            'token' => $plain,
            'workspace' => $employee->organization->slug,
        ]), [
            'password' => 'Replacement-Password2!',
            'password_confirmation' => 'Replacement-Password2!',
        ])->assertRedirect(route('login', ['workspace' => $employee->organization->slug]));

        $this->assertTrue(Hash::check('Replacement-Password2!', $employee->fresh()->password));
        $this->assertSame($previousVersion + 1, $employee->fresh()->auth_version);
        $this->assertNotNull($recovery->fresh()->used_at);
        $this->get(route('recovery.reset', [
            'actor' => 'employee',
            'token' => $plain,
            'workspace' => $employee->organization->slug,
        ]))->assertStatus(410);
    }

    public function test_recovery_request_does_not_disclose_whether_an_account_exists(): void
    {
        Notification::fake();
        $organization = Organization::where('slug', 'peopleos-demo')->firstOrFail();

        $response = $this->post(route('recovery.send', [
            'actor' => 'employee',
            'workspace' => $organization->slug,
        ]), ['email' => 'missing@example.com']);

        $response->assertSessionHas('success');
        Notification::assertNothingSent();
    }

    public function test_tenant_owner_can_invite_and_securely_activate_a_limited_administrator(): void
    {
        Notification::fake();
        $owner = Admin::create([
            'name' => 'Tenant Owner',
            'email' => 'tenant-owner@example.com',
            'password' => 'Owner-Password1!',
            'role' => 'owner',
        ]);
        $role = Role::where('slug', 'hr-admin')->firstOrFail();

        $response = $this->actingAs($owner, 'admin')->post(route('access.admins.invite'), [
            'name' => 'People Partner',
            'email' => 'partner@example.com',
            'role_id' => $role->id,
        ])->assertSessionHas('admin_invitation_url');

        $url = session('admin_invitation_url');
        preg_match('#/activar-administrador/([^?]+)#', $url, $matches);
        $plain = $matches[1];
        $this->post(route('admin-invitations.accept', [
            'token' => $plain,
            'workspace' => $owner->organization->slug,
        ]), [
            'password' => 'Partner-Password2!',
            'password_confirmation' => 'Partner-Password2!',
        ])->assertRedirect(route('admin.login', ['workspace' => $owner->organization->slug]));

        $admin = Admin::where('email', 'partner@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Partner-Password2!', $admin->password));
        $this->assertTrue($admin->roles()->whereKey($role->id)->exists());
        $this->get(route('admin-invitations.show', [
            'token' => $plain,
            'workspace' => $owner->organization->slug,
        ]))->assertStatus(410);
    }

    public function test_disabling_an_administrator_revokes_sessions_and_api_tokens(): void
    {
        $owner = Admin::create([
            'name' => 'Tenant Owner',
            'email' => 'owner-disable@example.com',
            'password' => 'Owner-Password1!',
            'role' => 'owner',
        ]);
        $admin = Admin::create([
            'name' => 'Departing Admin',
            'email' => 'departing@example.com',
            'password' => 'Departing-Password1!',
            'role' => 'hr_admin',
        ]);
        $token = ApiToken::create([
            'created_by' => $admin->id,
            'name' => 'Departing integration',
            'token_prefix' => 'pos_live_test',
            'token_hash' => hash('sha256', 'departing-token'),
            'abilities' => ['employees:read'],
            'expires_at' => now()->addMonth(),
        ]);
        $previousVersion = $admin->auth_version;

        $this->actingAs($owner, 'admin')
            ->delete(route('access.admins.disable', $admin))
            ->assertSessionHas('success');

        $this->assertSame('disabled', $admin->fresh()->status);
        $this->assertSame($previousVersion + 1, $admin->fresh()->auth_version);
        $this->assertNotNull($token->fresh()->revoked_at);
    }

    public function test_employee_capacity_is_enforced_at_the_write_boundary(): void
    {
        $organization = Organization::where('slug', 'peopleos-demo')->firstOrFail();
        $organization->update(['seat_limit' => 1]);
        $organization->subscription()->update(['licensed_seats' => 1]);
        User::factory()->create(['employment_status' => 'active']);
        $owner = Admin::create([
            'name' => 'Capacity Owner',
            'email' => 'capacity@example.com',
            'password' => 'Owner-Password1!',
            'role' => 'owner',
        ]);

        $this->actingAs($owner, 'admin')->post(route('empleados.store'), [
            'employee_code' => 'EMP-CAPACITY',
            'first_name' => 'No',
            'last_name' => 'Capacity',
            'email' => 'capacity-user@example.com',
            'username' => 'capacity.user',
            'departamento_id' => Departamento::firstOrFail()->id,
            'job_title' => 'Analyst',
            'employment_status' => 'active',
            'employment_type' => 'full_time',
            'hire_date' => now()->toDateString(),
            'location' => 'Bogotá',
            'password' => 'Employee-Password1!',
            'password_confirmation' => 'Employee-Password1!',
        ])->assertSessionHasErrors('seat_limit');

        $this->assertDatabaseMissing('users', ['email' => 'capacity-user@example.com']);
    }

    public function test_growth_plan_cannot_use_business_or_enterprise_features(): void
    {
        $organization = Organization::where('slug', 'peopleos-demo')->firstOrFail();
        $organization->subscription()->update([
            'plan_id' => Plan::where('code', 'growth')->value('id'),
            'licensed_seats' => 100,
        ]);
        $employee = User::factory()->create();

        $this->actingAs($employee)->get(route('reviews.index'))->assertStatus(402);
        $this->actingAs($employee)->get(route('leave.index'))->assertOk();
        $this->actingAs($employee)->get(route('expansion.index'))->assertStatus(402);
    }

    public function test_offboarding_revocation_changes_the_employee_credential_version(): void
    {
        $employee = User::factory()->create(['employment_status' => 'inactive']);
        $previousVersion = $employee->auth_version;

        app(AccessLifecycleService::class)->revokeEmployee($employee);

        $this->assertSame($previousVersion + 1, $employee->fresh()->auth_version);
        $this->assertNotNull($employee->fresh()->remember_token);
    }
}
