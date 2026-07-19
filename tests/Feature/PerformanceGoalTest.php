<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\PerformanceGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceGoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_goal_and_employee_can_report_completion(): void
    {
        $admin = Admin::create(['name' => 'HR Admin', 'email' => 'hr@example.com', 'password' => 'SecurePassword1!', 'role' => 'hr_admin']);
        $employee = User::factory()->create();

        $this->actingAs($admin, 'admin')->post(route('goals.store'), [
            'user_id' => $employee->id,
            'title' => 'Reducir tiempo de primera respuesta',
            'description' => 'Llevar el promedio del equipo a menos de dos horas.',
            'target_date' => today()->addMonth()->toDateString(),
        ])->assertRedirect(route('goals.index'));

        $goal = PerformanceGoal::firstOrFail();
        $this->assertSame(0, $goal->progress);
        $this->assertDatabaseHas('audit_logs', ['event' => 'goal.created', 'actor_id' => $admin->id]);

        $this->actingAs($employee)->patch(route('goals.progress', $goal), ['progress' => 100])
            ->assertSessionHas('success');

        $goal->refresh();
        $this->assertSame(100, $goal->progress);
        $this->assertSame('completed', $goal->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'goal.progressed', 'actor_id' => $employee->id]);
    }

    public function test_employee_cannot_update_another_employees_goal(): void
    {
        $owner = User::factory()->create();
        $otherEmployee = User::factory()->create();
        $goal = PerformanceGoal::create([
            'user_id' => $owner->id,
            'title' => 'Objetivo privado',
            'progress' => 10,
            'status' => 'active',
        ]);

        $this->actingAs($otherEmployee)->patch(route('goals.progress', $goal), ['progress' => 90])
            ->assertForbidden();
        $this->assertSame(10, $goal->fresh()->progress);
    }
}
