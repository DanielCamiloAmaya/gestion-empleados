<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Departamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::create(['name' => 'HR Admin', 'email' => 'hr@example.com', 'password' => 'SecurePassword1!', 'role' => 'hr_admin']);
    }

    public function test_admin_can_create_a_login_ready_employee_and_audit_is_recorded(): void
    {
        $department = Departamento::firstOrFail();

        $response = $this->actingAs($this->admin, 'admin')->post(route('empleados.store'), [
            'employee_code' => 'EMP-9001',
            'first_name' => 'Ana',
            'last_name' => 'Torres',
            'email' => 'ana@example.com',
            'username' => 'ana.torres',
            'departamento_id' => $department->id,
            'job_title' => 'People Partner',
            'employment_status' => 'active',
            'employment_type' => 'full_time',
            'hire_date' => now()->toDateString(),
            'phone' => '+57 300 000 0000',
            'location' => 'Bogotá',
            'manager_id' => null,
            'password' => 'UniquePassword1!',
            'password_confirmation' => 'UniquePassword1!',
        ]);

        $employee = User::where('email', 'ana@example.com')->firstOrFail();
        $response->assertRedirect(route('empleados.show', $employee));
        $this->assertTrue(Hash::check('UniquePassword1!', $employee->password), 'The password must be hashed exactly once.');
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee.created', 'auditable_id' => $employee->id, 'actor_id' => $this->admin->id]);
        $this->assertArrayNotHasKey('password', AuditLog::firstOrFail()->new_values);
    }

    public function test_archiving_employee_uses_soft_delete_and_records_audit(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($this->admin, 'admin')->delete(route('empleados.destroy', $employee))
            ->assertRedirect(route('empleados.index'));

        $this->assertSoftDeleted($employee);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee.archived', 'auditable_id' => $employee->id]);
    }

    public function test_department_with_employees_cannot_be_deleted(): void
    {
        $employee = User::factory()->create();
        $department = $employee->departamento;

        $this->actingAs($this->admin, 'admin')->delete(route('departamentos.destroy', $department))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('departamentos', ['id' => $department->id]);
    }
}
