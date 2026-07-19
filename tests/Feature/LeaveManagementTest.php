<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_submit_a_leave_request_and_it_is_audited(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)->post(route('leave.store'), [
            'type' => 'vacation',
            'start_date' => today()->addDays(10)->toDateString(),
            'end_date' => today()->addDays(12)->toDateString(),
            'reason' => 'Vacaciones familiares planificadas con anticipación.',
        ])->assertRedirect(route('leave.index'));

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $employee->id,
            'days' => 3,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'leave.requested', 'actor_id' => $employee->id]);
    }

    public function test_overlapping_pending_or_approved_leave_is_rejected(): void
    {
        $employee = User::factory()->create();
        LeaveRequest::create([
            'user_id' => $employee->id,
            'type' => 'vacation',
            'start_date' => today()->addDays(10),
            'end_date' => today()->addDays(12),
            'days' => 3,
            'reason' => 'Primera solicitud válida para probar cruces.',
            'status' => 'pending',
        ]);

        $this->actingAs($employee)->post(route('leave.store'), [
            'type' => 'personal',
            'start_date' => today()->addDays(11)->toDateString(),
            'end_date' => today()->addDays(13)->toDateString(),
            'reason' => 'Esta solicitud coincide con un periodo ya solicitado.',
        ])->assertSessionHasErrors('start_date');

        $this->assertDatabaseCount('leave_requests', 1);
    }

    public function test_employee_only_sees_their_own_requests(): void
    {
        $employee = User::factory()->create();
        $otherEmployee = User::factory()->create();
        $own = LeaveRequest::create([
            'user_id' => $employee->id,
            'type' => 'personal',
            'start_date' => today()->addDays(5),
            'end_date' => today()->addDays(5),
            'days' => 1,
            'reason' => 'Solicitud personal que pertenece al usuario autenticado.',
        ]);
        $other = LeaveRequest::create([
            'user_id' => $otherEmployee->id,
            'type' => 'medical',
            'start_date' => today()->addDays(8),
            'end_date' => today()->addDays(8),
            'days' => 1,
            'reason' => 'Solicitud privada que no debe aparecer a otro empleado.',
        ]);

        $this->actingAs($employee)->get(route('leave.index'))
            ->assertOk()
            ->assertSee($own->reason)
            ->assertDontSee($other->reason);
    }

    public function test_admin_can_approve_only_a_pending_request(): void
    {
        $admin = Admin::create(['name' => 'HR Admin', 'email' => 'hr@example.com', 'password' => 'SecurePassword1!', 'role' => 'hr_admin']);
        $leave = LeaveRequest::create([
            'user_id' => User::factory()->create()->id,
            'type' => 'vacation',
            'start_date' => today()->addDays(10),
            'end_date' => today()->addDays(11),
            'days' => 2,
            'reason' => 'Solicitud pendiente que será revisada por recursos humanos.',
        ]);

        $this->actingAs($admin, 'admin')->patch(route('leave.review', $leave), ['status' => 'approved'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'leave.approved', 'actor_id' => $admin->id]);

        $this->actingAs($admin, 'admin')->patch(route('leave.review', $leave), ['status' => 'rejected', 'review_note' => 'Cambio tardío'])
            ->assertSessionHas('error');
        $this->assertSame('approved', $leave->fresh()->status);
    }
}
