<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\OnboardingDeliverableFile;
use App\Models\OnboardingSubmission;
use App\Models\OnboardingTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeliverableWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('deliverables');
    }

    public function test_employee_uploads_a_private_versioned_deliverable(): void
    {
        $employee = User::factory()->create();
        $task = $this->taskFor($employee);
        $file = UploadedFile::fake()->createWithContent('informe-final.pdf', "%PDF-1.4\nPeopleOS enterprise deliverable");

        $this->actingAs($employee)->post(route('deliverables.store', $task), [
            'message' => 'Adjunto el informe final con los resultados y recomendaciones.',
            'files' => [$file],
        ])->assertRedirect(route('onboarding.show', $task));

        $submission = OnboardingSubmission::firstOrFail();
        $deliverable = OnboardingDeliverableFile::firstOrFail();

        $this->assertSame(1, $submission->version);
        $this->assertSame('submitted', $submission->status);
        $this->assertSame('in_progress', $task->fresh()->status);
        $this->assertSame('informe-final.pdf', $deliverable->original_name);
        $this->assertNotSame('informe-final.pdf', basename($deliverable->storage_path));
        $this->assertSame(64, strlen($deliverable->sha256));
        Storage::disk('deliverables')->assertExists($deliverable->storage_path);
        $this->assertDatabaseHas('audit_logs', ['event' => 'deliverable.submitted', 'actor_id' => $employee->id]);
        $this->actingAs($employee)->get(route('deliverables.download', $deliverable))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_direct_manager_can_approve_and_complete_the_task(): void
    {
        $manager = User::factory()->create();
        $employee = User::factory()->create(['manager_id' => $manager->id]);
        $task = $this->taskFor($employee);
        $submission = $this->submissionFor($task, $employee);
        $deliverable = OnboardingDeliverableFile::create([
            'onboarding_submission_id' => $submission->id,
            'original_name' => 'informe-gerencial.pdf',
            'storage_path' => 'task-'.$task->id.'/informe-gerencial.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 100,
            'sha256' => str_repeat('b', 64),
        ]);
        Storage::disk('deliverables')->put($deliverable->storage_path, 'private');

        $this->actingAs($manager)->get(route('onboarding.index'))->assertOk()->assertSee($task->title);
        $this->actingAs($manager)->get(route('onboarding.show', $task))->assertOk()->assertSee('Aprobar entrega');
        $this->actingAs($manager)->get(route('deliverables.download', $deliverable))->assertOk();

        $this->actingAs($manager)->patch(route('deliverables.review', $submission), [
            'status' => 'approved',
            'review_note' => 'El informe cumple los criterios y contiene evidencia suficiente.',
        ])->assertRedirect(route('onboarding.show', $task));

        $submission->refresh();
        $this->assertSame('approved', $submission->status);
        $this->assertSame($manager->id, $submission->reviewed_by_user_id);
        $this->assertSame('completed', $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
        $this->assertDatabaseHas('audit_logs', ['event' => 'deliverable.approved', 'actor_id' => $manager->id]);
    }

    public function test_rejection_requires_reason_and_allows_a_new_version(): void
    {
        $admin = Admin::create(['name' => 'HR Admin', 'email' => 'hr@example.com', 'password' => 'SecurePassword1!', 'role' => 'hr_admin']);
        $employee = User::factory()->create();
        $task = $this->taskFor($employee);
        $firstSubmission = $this->submissionFor($task, $employee);

        $this->actingAs($admin, 'admin')->patch(route('deliverables.review', $firstSubmission), ['status' => 'rejected'])
            ->assertSessionHasErrors('review_note');

        $this->actingAs($admin, 'admin')->patch(route('deliverables.review', $firstSubmission), [
            'status' => 'rejected',
            'review_note' => 'Falta incluir la matriz de riesgos y las conclusiones ejecutivas.',
        ])->assertRedirect(route('onboarding.show', $task));

        $this->assertSame('rejected', $firstSubmission->fresh()->status);

        $this->actingAs($employee)->post(route('deliverables.store', $task), [
            'message' => 'Nueva versión con la matriz y las conclusiones solicitadas.',
            'files' => [UploadedFile::fake()->createWithContent('informe-v2.pdf', "%PDF-1.4\nCorrected version")],
        ])->assertRedirect(route('onboarding.show', $task));

        $this->assertDatabaseHas('onboarding_submissions', [
            'onboarding_task_id' => $task->id,
            'version' => 2,
            'status' => 'submitted',
        ]);
    }

    public function test_unrelated_employee_cannot_view_review_or_download_deliverables(): void
    {
        $employee = User::factory()->create();
        $unrelated = User::factory()->create();
        $task = $this->taskFor($employee);
        $submission = $this->submissionFor($task, $employee);
        $deliverable = OnboardingDeliverableFile::create([
            'onboarding_submission_id' => $submission->id,
            'original_name' => 'confidencial.pdf',
            'storage_path' => 'task-'.$task->id.'/confidencial.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 100,
            'sha256' => str_repeat('a', 64),
        ]);
        Storage::disk('deliverables')->put($deliverable->storage_path, 'private');

        $this->actingAs($unrelated)->get(route('onboarding.show', $task))->assertForbidden();
        $this->actingAs($unrelated)->get(route('deliverables.download', $deliverable))->assertForbidden();
        $this->actingAs($unrelated)->patch(route('deliverables.review', $submission), [
            'status' => 'approved',
        ])->assertForbidden();
    }

    public function test_pending_submission_prevents_duplicate_concurrent_delivery(): void
    {
        $employee = User::factory()->create();
        $task = $this->taskFor($employee);
        $this->submissionFor($task, $employee);

        $this->actingAs($employee)->post(route('deliverables.store', $task), [
            'files' => [UploadedFile::fake()->createWithContent('duplicado.pdf', "%PDF-1.4\nDuplicate")],
        ])->assertForbidden();

        $this->assertDatabaseCount('onboarding_submissions', 1);
    }

    public function test_enabled_antivirus_fails_closed_when_scanner_is_unavailable(): void
    {
        config()->set('deliverables.antivirus_enabled', true);
        config()->set('deliverables.antivirus_binary', 'missing-clamscan-binary');
        $employee = User::factory()->create();
        $task = $this->taskFor($employee);

        $this->actingAs($employee)->post(route('deliverables.store', $task), [
            'files' => [UploadedFile::fake()->createWithContent('informe.pdf', "%PDF-1.4\nSafe content")],
        ])->assertServerError();

        $this->assertDatabaseCount('onboarding_submissions', 0);
        Storage::disk('deliverables')->assertDirectoryEmpty('/');
    }

    private function taskFor(User $employee): OnboardingTask
    {
        return OnboardingTask::create([
            'user_id' => $employee->id,
            'title' => 'Preparar informe ejecutivo',
            'description' => 'Entregar un informe con resultados, evidencia y recomendaciones.',
            'priority' => 'high',
            'status' => 'pending',
        ]);
    }

    private function submissionFor(OnboardingTask $task, User $employee): OnboardingSubmission
    {
        return OnboardingSubmission::create([
            'onboarding_task_id' => $task->id,
            'submitted_by' => $employee->id,
            'version' => 1,
            'message' => 'Primera versión para revisión.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }
}
