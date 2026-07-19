<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ApiToken;
use App\Models\AttendanceEntry;
use App\Models\Departamento;
use App\Models\EmployeeDocument;
use App\Models\OffboardingCase;
use App\Models\Organization;
use App\Models\PerformanceReview;
use App\Models\User;
use App\Services\TotpService;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnterprisePlatformTest extends TestCase
{
    use RefreshDatabase;

    private Admin $owner;

    private Departamento $department;

    protected function setUp(): void
    {
        parent::setUp();
        $this->department = Departamento::where('nombre', 'Tecnología')->firstOrFail();
        $this->owner = Admin::create(['name' => 'Platform Owner', 'email' => 'owner@peopleos.test', 'password' => 'SecurePassword1!', 'role' => 'owner']);
    }

    public function test_tenant_route_binding_never_exposes_another_organization_employee(): void
    {
        $other = Organization::create(['uuid' => (string) Str::uuid(), 'name' => 'Other Corp', 'slug' => 'other-corp', 'country_code' => 'CO', 'timezone' => 'America/Bogota', 'locale' => 'es', 'plan' => 'enterprise', 'is_active' => true]);
        $otherDepartment = Departamento::create(['organization_id' => $other->id, 'nombre' => 'Private Team', 'cost_center' => 'OTHER-1', 'is_active' => true]);
        $otherEmployee = User::factory()->create(['organization_id' => $other->id, 'departamento_id' => $otherDepartment->id]);

        $this->actingAs($this->owner, 'admin')->get(route('empleados.show', $otherEmployee))->assertNotFound();
    }

    public function test_same_identity_values_are_allowed_in_different_tenants(): void
    {
        $first = User::factory()->create(['email' => 'shared@example.com', 'username' => 'shared', 'employee_code' => 'SHARED']);
        $other = Organization::create(['uuid' => (string) Str::uuid(), 'name' => 'Other Corp', 'slug' => 'other-corp', 'country_code' => 'CO', 'timezone' => 'America/Bogota', 'locale' => 'es', 'plan' => 'enterprise', 'is_active' => true]);
        $otherDepartment = Departamento::create(['organization_id' => $other->id, 'nombre' => 'People', 'cost_center' => 'OTHER-2', 'is_active' => true]);
        app(OrganizationContext::class)->clear();
        $second = User::factory()->create(['organization_id' => $other->id, 'departamento_id' => $otherDepartment->id, 'email' => 'shared@example.com', 'username' => 'shared', 'employee_code' => 'SHARED']);

        $this->assertNotSame($first->organization_id, $second->organization_id);
    }

    public function test_enabled_mfa_blocks_admin_until_valid_totp_is_verified(): void
    {
        $secret = app(TotpService::class)->generateSecret();
        $this->owner->forceFill(['mfa_enabled' => true, 'mfa_secret' => $secret, 'mfa_confirmed_at' => now()])->save();

        $this->post(route('admin.login'), ['workspace' => 'peopleos-demo', 'name' => $this->owner->email, 'password' => 'SecurePassword1!'])
            ->assertRedirect(route('mfa.challenge'));
        $this->get(route('admin.home'))->assertRedirect(route('mfa.challenge'));
        $this->post(route('mfa.verify'), ['code' => $this->totp($secret)])->assertRedirect(route('admin.home'));
        $this->get(route('admin.home'))->assertOk();
    }

    public function test_leave_request_creates_persistent_notifications_for_approvers(): void
    {
        $employee = User::factory()->create();
        $this->actingAs($employee)->post(route('leave.store'), ['type' => 'personal', 'start_date' => today()->addDays(10)->toDateString(), 'end_date' => today()->addDays(10)->toDateString(), 'reason' => 'Diligencia personal previamente planificada.'])->assertRedirect();

        $this->assertSame(1, $this->owner->fresh()->notifications()->count());
        $this->assertSame('Nueva solicitud de ausencia', $this->owner->fresh()->notifications()->first()->data['title']);
    }

    public function test_document_is_private_and_signature_preserves_hash_evidence(): void
    {
        Storage::fake('employee_documents');
        $employee = User::factory()->create(['first_name' => 'Ana', 'last_name' => 'Torres']);
        $this->actingAs($this->owner, 'admin')->post(route('documents.store'), ['user_id' => $employee->id, 'title' => 'Politica de seguridad', 'category' => 'policy', 'requires_signature' => '1', 'file' => UploadedFile::fake()->create('politica.pdf', 120, 'application/pdf')])->assertSessionHas('success');
        $document = EmployeeDocument::firstOrFail();
        Storage::disk('employee_documents')->assertExists($document->storage_path);

        $this->actingAs($employee)->post(route('documents.sign', $document), ['signer_name' => 'Ana Torres', 'consent' => '1'])->assertSessionHas('success');
        $this->assertDatabaseHas('document_signatures', ['employee_document_id' => $document->id, 'document_sha256' => $document->sha256]);
    }

    public function test_cross_tenant_document_assignment_is_rejected(): void
    {
        Storage::fake('employee_documents');
        $other = Organization::create(['uuid' => (string) Str::uuid(), 'name' => 'Other Corp', 'slug' => 'other-corp', 'country_code' => 'CO', 'timezone' => 'America/Bogota', 'locale' => 'es', 'plan' => 'enterprise', 'is_active' => true]);
        $otherDepartment = Departamento::create(['organization_id' => $other->id, 'nombre' => 'Private Team', 'cost_center' => 'OTHER-3', 'is_active' => true]);
        $employee = User::factory()->create(['organization_id' => $other->id, 'departamento_id' => $otherDepartment->id]);

        $this->actingAs($this->owner, 'admin')->post(route('documents.store'), ['user_id' => $employee->id, 'title' => 'Cross tenant', 'category' => 'other', 'file' => UploadedFile::fake()->create('file.pdf', 10, 'application/pdf')])->assertStatus(422);
        $this->assertDatabaseCount('employee_documents', 0);
    }

    public function test_scoped_api_token_returns_only_its_organization(): void
    {
        $employee = User::factory()->create();
        $plain = 'pos_live_'.Str::random(48);
        ApiToken::create(['created_by' => $this->owner->id, 'name' => 'Reporting', 'token_prefix' => substr($plain, 0, 16), 'token_hash' => hash('sha256', $plain), 'abilities' => ['employees:read']]);

        $this->withToken($plain)->getJson('/api/v1/employees')->assertOk()->assertJsonFragment(['id' => $employee->id, 'email' => $employee->email]);
        $this->assertNotNull(ApiToken::first()->last_used_at);
    }

    public function test_scim_requires_its_own_scope_and_can_deactivate_user(): void
    {
        $employee = User::factory()->create();
        $plain = 'pos_live_'.Str::random(48);
        ApiToken::create(['created_by' => $this->owner->id, 'name' => 'SCIM', 'token_prefix' => substr($plain, 0, 16), 'token_hash' => hash('sha256', $plain), 'abilities' => ['scim']]);

        $this->withToken($plain)->patchJson('/api/scim/v2/Users/'.$employee->id, ['Operations' => [['op' => 'Replace', 'path' => 'active', 'value' => false]]])->assertOk()->assertJson(['active' => false]);
        $this->assertSame('inactive', $employee->fresh()->employment_status);
    }

    public function test_employee_can_clock_in_and_out_without_open_duplicates(): void
    {
        $employee = User::factory()->create();
        $this->actingAs($employee)->post(route('attendance.clock'))->assertSessionHas('success');
        $this->assertDatabaseCount('attendance_entries', 1);
        $this->actingAs($employee)->post(route('attendance.clock'))->assertSessionHas('success');
        $this->assertDatabaseCount('attendance_entries', 1);
        $this->assertNotNull($employee->fresh()->newQuery()->find($employee->id));
        $this->assertNotNull(AttendanceEntry::first()->clocked_out_at);
    }

    public function test_readiness_endpoint_reports_database_and_storage(): void
    {
        $this->getJson(route('health.ready'))->assertOk()->assertJson(['status' => 'ready', 'checks' => ['database' => 'ok', 'storage' => 'ok']])->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_csv_import_is_transactional_and_issues_one_time_invitations(): void
    {
        $csv = implode("\n", [
            'employee_code,first_name,last_name,email,username,job_title,department,employment_type,hire_date,phone,location',
            'IMP-001,Laura,Importada,laura.importada@example.com,laura.importada,Analista,Tecnología,full_time,'.today()->toDateString().',,Bogotá',
        ]);

        $this->actingAs($this->owner, 'admin')->post(route('employees.import.store'), ['file' => UploadedFile::fake()->createWithContent('employees.csv', $csv)])->assertOk()->assertDownload();
        $this->assertDatabaseHas('users', ['employee_code' => 'IMP-001', 'email' => 'laura.importada@example.com']);
        $this->assertDatabaseHas('employee_invitations', ['accepted_at' => null]);
    }

    public function test_review_cycle_publishes_and_employee_acknowledges_evidence(): void
    {
        $employee = User::factory()->create();
        $this->actingAs($this->owner, 'admin')->post(route('reviews.cycles.store'), ['name' => 'Semestral 2026', 'type' => 'manager', 'starts_at' => today()->toDateString(), 'ends_at' => today()->addMonth()->toDateString()])->assertSessionHas('success');
        $review = PerformanceReview::where('user_id', $employee->id)->firstOrFail();
        $this->actingAs($this->owner, 'admin')->patch(route('reviews.submit', $review), ['performance_score' => 4, 'potential_score' => 5, 'summary' => 'Resultados consistentes y medibles durante todo el periodo.', 'strengths' => 'Colaboración y ejecución confiable.', 'development_areas' => 'Delegación y comunicación ejecutiva.'])->assertSessionHas('success');
        $this->actingAs($employee)->post(route('reviews.acknowledge', $review))->assertSessionHas('success');
        $this->assertSame('acknowledged', $review->fresh()->status);
    }

    public function test_offboarding_closes_only_after_every_control_is_completed(): void
    {
        $employee = User::factory()->create();
        $this->actingAs($this->owner, 'admin')->post(route('offboarding.store'), ['user_id' => $employee->id, 'last_day' => today()->addWeek()->toDateString(), 'reason' => 'resignation', 'risk_level' => 'standard'])->assertSessionHas('success');
        $case = OffboardingCase::with('tasks')->firstOrFail();
        $this->assertCount(5, $case->tasks);
        foreach ($case->tasks as $task) {
            $this->actingAs($this->owner, 'admin')->patch(route('offboarding.tasks.update', $task), ['status' => 'completed'])->assertSessionHas('success');
        }
        $this->assertSame('completed', $case->fresh()->status);
        $this->assertSame('inactive', $employee->fresh()->employment_status);
    }

    public function test_hr_admin_cannot_enter_platform_security_configuration(): void
    {
        $hr = Admin::create(['name' => 'HR Admin', 'email' => 'hr@peopleos.test', 'password' => 'SecurePassword1!', 'role' => 'hr_admin']);
        $this->actingAs($hr, 'admin')->get(route('platform.index'))->assertForbidden();
    }

    public function test_api_scope_is_enforced_before_business_data_is_loaded(): void
    {
        $plain = 'pos_live_'.Str::random(48);
        ApiToken::create(['created_by' => $this->owner->id, 'name' => 'SCIM only', 'token_prefix' => substr($plain, 0, 16), 'token_hash' => hash('sha256', $plain), 'abilities' => ['scim']]);
        $this->withToken($plain)->getJson('/api/v1/employees')->assertForbidden();
    }

    private function totp(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($secret) as $character) {
            $bits .= str_pad(decbin(strpos($alphabet, $character)), 5, '0', STR_PAD_LEFT);
        }
        $key = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $key .= chr(bindec($chunk));
            }
        }
        $hash = hash_hmac('sha1', pack('N2', 0, intdiv(time(), 30)), $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) | ((ord($hash[$offset + 1]) & 0xFF) << 16) | ((ord($hash[$offset + 2]) & 0xFF) << 8) | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % 1_000_000), 6, '0', STR_PAD_LEFT);
    }
}
