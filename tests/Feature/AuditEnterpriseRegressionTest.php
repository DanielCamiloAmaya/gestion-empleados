<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditEnterpriseRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_existing_employee_session_is_revoked_when_employment_becomes_inactive(): void
    {
        $employee = User::factory()->create(['employment_status' => 'active']);

        $this->actingAs($employee)->get(route('home'))->assertOk();
        $employee->update(['employment_status' => 'inactive']);

        $this->get(route('home'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
