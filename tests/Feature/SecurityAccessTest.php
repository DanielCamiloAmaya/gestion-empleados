<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_corporate_data(): void
    {
        $this->get(route('empleados.index'))->assertRedirect(route('login'));
        $this->get(route('departamentos.index'))->assertRedirect(route('login'));
    }

    public function test_public_registration_endpoints_do_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
        $this->get('/admin/register')->assertNotFound();
        $this->post('/admin/register')->assertNotFound();
    }

    public function test_employee_can_read_directory_but_cannot_mutate_it(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)
            ->get(route('empleados.index'))
            ->assertOk()
            ->assertDontSee(route('empleados.create'));

        $this->actingAs($employee)
            ->post(route('empleados.store'), [])
            ->assertRedirect(route('admin.login'));
    }

    public function test_security_headers_are_attached_to_responses(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_admin_can_access_governance_area(): void
    {
        $admin = Admin::create(['name' => 'HR Admin', 'email' => 'hr@example.com', 'password' => 'SecurePassword1!', 'role' => 'hr_admin']);

        $this->actingAs($admin, 'admin')->get(route('audit.index'))->assertOk();
    }
}
