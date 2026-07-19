<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\OnboardingTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_assigns_a_task_that_starts_pending_review(): void
    {
        $admin = Admin::create(['name' => 'HR Admin', 'email' => 'hr@example.com', 'password' => 'SecurePassword1!', 'role' => 'hr_admin']);
        $employee = User::factory()->create();

        $this->actingAs($admin, 'admin')->post(route('onboarding.store'), [
            'user_id' => $employee->id,
            'title' => 'Completar inducción de seguridad',
            'description' => 'Revisar la política y confirmar su comprensión.',
            'due_date' => today()->addWeek()->toDateString(),
            'priority' => 'high',
        ])->assertRedirect(route('onboarding.index'));

        $task = OnboardingTask::firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['event' => 'onboarding.created', 'actor_id' => $admin->id]);
        $this->assertSame('pending', $task->status);
        $this->assertNull($task->completed_at);
    }

    public function test_employee_cannot_bypass_deliverable_review_on_own_task(): void
    {
        $employee = User::factory()->create();
        $task = OnboardingTask::create([
            'user_id' => $employee->id,
            'title' => 'Entregar documentación profesional',
            'priority' => 'high',
            'status' => 'pending',
        ]);

        $this->actingAs($employee)->patch(route('onboarding.status', $task), ['status' => 'completed'])
            ->assertForbidden();

        $this->assertSame('pending', $task->fresh()->status);
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_employee_cannot_update_another_employees_task(): void
    {
        $taskOwner = User::factory()->create();
        $otherEmployee = User::factory()->create();
        $task = OnboardingTask::create([
            'user_id' => $taskOwner->id,
            'title' => 'Tarea privada de onboarding',
            'priority' => 'medium',
            'status' => 'pending',
        ]);

        $this->actingAs($otherEmployee)->patch(route('onboarding.status', $task), ['status' => 'completed'])
            ->assertForbidden();
        $this->assertSame('pending', $task->fresh()->status);
    }
}
