<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeliverableReviewRequest;
use App\Http\Requests\DeliverableStoreRequest;
use App\Models\AuditLog;
use App\Models\OnboardingDeliverableFile;
use App\Models\OnboardingSubmission;
use App\Models\OnboardingTask;
use App\Services\DeliverableMalwareScanner;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class OnboardingDeliverableController extends Controller
{
    public function store(DeliverableStoreRequest $request, OnboardingTask $task, DeliverableMalwareScanner $malwareScanner, NotificationService $notifications)
    {
        $storedPaths = [];

        foreach ($request->file('files') as $uploadedFile) {
            $malwareScanner->assertClean($uploadedFile->getRealPath());
        }

        try {
            $submission = DB::transaction(function () use ($request, $task, &$storedPaths) {
                $version = (int) $task->submissions()->lockForUpdate()->max('version') + 1;
                $submission = OnboardingSubmission::create([
                    'onboarding_task_id' => $task->id,
                    'submitted_by' => auth()->id(),
                    'version' => $version,
                    'message' => $request->validated('message'),
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);

                foreach ($request->file('files') as $uploadedFile) {
                    $extension = Str::lower($uploadedFile->getClientOriginalExtension());
                    $storageName = Str::uuid().($extension ? ".{$extension}" : '');
                    $storagePath = $uploadedFile->storeAs("task-{$task->id}/version-{$version}", $storageName, 'deliverables');
                    $storedPaths[] = $storagePath;

                    $submission->files()->create([
                        'original_name' => $this->safeOriginalName($uploadedFile->getClientOriginalName()),
                        'storage_path' => $storagePath,
                        'mime_type' => $uploadedFile->getMimeType() ?: 'application/octet-stream',
                        'extension' => $extension,
                        'size_bytes' => $uploadedFile->getSize(),
                        'sha256' => hash_file('sha256', $uploadedFile->getRealPath()),
                    ]);
                }

                $task->update(['status' => 'in_progress', 'completed_at' => null]);
                AuditLog::record($request, 'deliverable.submitted', $submission, [], [
                    'task_id' => $task->id,
                    'version' => $version,
                    'files' => $submission->files()->pluck('original_name')->all(),
                ]);

                return $submission;
            });
        } catch (Throwable $exception) {
            Storage::disk('deliverables')->delete($storedPaths);
            throw $exception;
        }

        $notifications->admins('approvals.review', [
            'title' => 'Entregable listo para revision',
            'body' => $task->employee->full_name.' envio la version '.$submission->version.' de '.$task->title.'.',
            'url' => route('approvals.index'),
            'category' => 'approval',
        ]);
        $notifications->manager($task->employee->manager, [
            'title' => 'Entregable de tu equipo',
            'body' => $task->employee->full_name.' espera tu revision.',
            'url' => route('approvals.index'),
            'category' => 'approval',
        ]);

        return redirect()->route('onboarding.show', $task)
            ->with('success', "Entrega v{$submission->version} enviada para revisión.");
    }

    public function review(DeliverableReviewRequest $request, OnboardingSubmission $submission, NotificationService $notifications)
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $submission, $data) {
            $old = $submission->toArray();
            $submission->update([
                'status' => $data['status'],
                'review_note' => $data['review_note'] ?? null,
                'reviewed_by_admin_id' => auth('admin')->id(),
                'reviewed_by_user_id' => auth('admin')->check() ? null : auth()->id(),
                'reviewed_at' => now(),
            ]);

            $submission->task->update([
                'status' => $data['status'] === 'approved' ? 'completed' : 'in_progress',
                'completed_at' => $data['status'] === 'approved' ? now() : null,
            ]);

            AuditLog::record($request, 'deliverable.'.$data['status'], $submission, $old, $submission->fresh()->toArray());
        });

        $notifications->employee($submission->task->employee, [
            'title' => $data['status'] === 'approved' ? 'Entregable aprobado' : 'Entregable devuelto',
            'body' => $data['status'] === 'approved' ? $submission->task->title.' fue completada.' : ($data['review_note'] ?? 'Revisa los comentarios y envia una nueva version.'),
            'url' => route('onboarding.show', $submission->task),
            'severity' => $data['status'] === 'approved' ? 'success' : 'warning',
            'category' => 'approval',
        ]);

        $message = $data['status'] === 'approved'
            ? 'Entrega aprobada y tarea completada.'
            : 'Entrega rechazada. El empleado puede enviar una nueva versión.';

        return redirect()->route('onboarding.show', $submission->task)->with('success', $message);
    }

    public function download(OnboardingDeliverableFile $deliverableFile)
    {
        $deliverableFile->load('submission.task.employee');
        $task = $deliverableFile->submission->task;

        abort_unless($this->canAccess($task), 403);
        abort_unless(Storage::disk('deliverables')->exists($deliverableFile->storage_path), 404);

        return Storage::disk('deliverables')->download(
            $deliverableFile->storage_path,
            $deliverableFile->original_name,
            ['Content-Type' => $deliverableFile->mime_type, 'X-Content-Type-Options' => 'nosniff'],
        );
    }

    private function canAccess(OnboardingTask $task): bool
    {
        return auth('admin')->check()
            || (auth()->check() && ($task->user_id === auth()->id() || $task->employee->manager_id === auth()->id()));
    }

    private function safeOriginalName(string $name): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $name))) ?: 'entregable';

        return Str::limit($name, 240, '');
    }
}
