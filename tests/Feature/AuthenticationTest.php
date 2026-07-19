<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_employee_can_sign_in_and_sign_out(): void
    {
        $employee = User::factory()->create(['username' => 'daniel', 'password' => 'SecurePassword1!']);

        $this->post(route('login'), ['username' => 'daniel', 'password' => 'SecurePassword1!'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($employee);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_inactive_employee_is_denied_even_with_valid_credentials(): void
    {
        User::factory()->inactive()->create(['username' => 'inactive', 'password' => 'SecurePassword1!']);

        $this->post(route('login'), ['username' => 'inactive', 'password' => 'SecurePassword1!'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_admin_login_records_last_access(): void
    {
        $admin = Admin::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'SecurePassword1!', 'role' => 'owner']);

        $this->post(route('admin.login'), ['name' => 'owner@example.com', 'password' => 'SecurePassword1!'])
            ->assertRedirect(route('admin.home'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNotNull($admin->fresh()->last_login_at);
    }
}
