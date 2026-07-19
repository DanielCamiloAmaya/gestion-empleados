@extends('layouts.app-master')
@section('title', $task->title)
@section('eyebrow', 'Entrega y revisión')
@section('page-title', 'Detalle de la tarea')

@section('content')
    <div class="page-heading reveal-item">
        <div>
            <a class="back-link" href="{{ route('onboarding.index') }}">← Volver a onboarding</a>
            <h2>{{ $task->title }}</h2>
            <p>{{ $task->description ?: 'Sin instrucciones adicionales.' }}</p>
        </div>
        <span class="status-chip status-{{ $task->status }}">{{ __('ops.task_status.'.$task->status) }}</span>
    </div>

    <section class="delivery-hero reveal-item">
        <div class="delivery-person">
            <span class="avatar avatar-large">{{ $task->employee->initials }}</span>
            <div><small>Responsable</small><strong>{{ $task->employee->full_name }}</strong><span>{{ $task->employee->job_title }} · {{ $task->employee->departamento?->nombre }}</span></div>
        </div>
        <dl class="delivery-meta">
            <div><dt>Fecha límite</dt><dd>{{ $task->due_date?->translatedFormat('d M Y') ?? 'Sin fecha' }}</dd></div>
            <div><dt>Prioridad</dt><dd>{{ __('ops.priority.'.$task->priority) }}</dd></div>
            <div><dt>Revisor</dt><dd>{{ $task->employee->manager?->full_name ?? 'RR. HH.' }}</dd></div>
        </dl>
    </section>

    <div class="delivery-layout reveal-item">
        <main class="delivery-main">
            @if($isOwner && $task->status !== 'completed')
                @if($hasPendingReview)
                    <div class="review-waiting">
                        <span class="review-waiting-icon">◷</span>
                        <div><strong>Tu entrega está en revisión</strong><p>Cuando el revisor decida, podrás consultar aquí la aprobación o los cambios solicitados.</p></div>
                    </div>
                @else
                    <form class="upload-card" method="POST" action="{{ route('deliverables.store', $task) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="upload-heading"><span class="upload-icon">↑</span><div><h3>{{ $task->submissions->isEmpty() ? 'Enviar entregables' : 'Enviar nueva versión' }}</h3><p>Adjunta la evidencia profesional que demuestra el cumplimiento de la tarea.</p></div></div>
                        <label class="file-drop" for="files">
                            <strong>Selecciona o arrastra tus archivos</strong>
                            <span>PDF, Office, OpenDocument, datos, ZIP o imágenes · Máx. 8 archivos de 25 MB</span>
                            <input id="files" type="file" name="files[]" multiple required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.odp,.rtf,.csv,.txt,.md,.json,.xml,.zip,.png,.jpg,.jpeg">
                        </label>
                        <div class="field"><label for="message">Mensaje para el revisor</label><textarea class="textarea" id="message" name="message" maxlength="2000" placeholder="Resume qué entregas, qué decisiones tomaste y cualquier contexto importante…">{{ old('message') }}</textarea></div>
                        <div class="upload-actions"><span>Los documentos se almacenan de forma privada.</span><button class="button button-primary" type="submit">Enviar a revisión →</button></div>
                    </form>
                @endif
            @endif

            <section class="submission-history">
                <div class="section-heading"><div><h3>Historial de entregas</h3><p>Cada reenvío conserva su versión, archivos y decisión.</p></div><span>{{ $task->submissions->count() }} versión(es)</span></div>

                @forelse($task->submissions as $submission)
                    <article class="submission-card submission-card-{{ $submission->status }}">
                        <header>
                            <div><span class="version-mark">v{{ $submission->version }}</span><div><strong>Entrega del {{ $submission->submitted_at->translatedFormat('d M Y · H:i') }}</strong><small>Por {{ $submission->submitter?->full_name ?? $task->employee->full_name }}</small></div></div>
                            <span class="status-chip submission-{{ $submission->status }}">{{ __('ops.submission_status.'.$submission->status) }}</span>
                        </header>

                        @if($submission->message)<p class="submission-message">{{ $submission->message }}</p>@endif

                        <div class="file-list">
                            @foreach($submission->files as $file)
                                <a class="file-item" href="{{ route('deliverables.download', $file) }}">
                                    <span class="file-type">{{ strtoupper($file->extension) }}</span>
                                    <span><strong>{{ $file->original_name }}</strong><small>{{ $file->human_size }} · SHA-256 verificado</small></span>
                                    <span>↓</span>
                                </a>
                            @endforeach
                        </div>

                        @if($submission->reviewed_at)
                            <div class="review-result review-result-{{ $submission->status }}">
                                <strong>{{ $submission->status === 'approved' ? 'Aprobada' : 'Cambios solicitados' }} por {{ $submission->reviewer_name }}</strong>
                                <time datetime="{{ $submission->reviewed_at->toIso8601String() }}">{{ $submission->reviewed_at->translatedFormat('d M Y · H:i') }}</time>
                                @if($submission->review_note)<p>{{ $submission->review_note }}</p>@endif
                            </div>
                        @endif

                        @if($canReview && $submission->status === 'submitted')
                            <form class="review-form" method="POST" action="{{ route('deliverables.review', $submission) }}">
                                @csrf @method('PATCH')
                                <div class="field"><label for="review_note_{{ $submission->id }}">Retroalimentación de la revisión</label><textarea class="textarea" id="review_note_{{ $submission->id }}" name="review_note" maxlength="2000" placeholder="Explica qué está bien o qué debe corregirse. El motivo es obligatorio al rechazar."></textarea></div>
                                <div class="review-actions"><button class="button button-danger" type="submit" name="status" value="rejected">Solicitar cambios</button><button class="button button-primary" type="submit" name="status" value="approved">Aprobar entrega</button></div>
                            </form>
                        @endif
                    </article>
                @empty
                    <div class="panel empty-state"><strong>Aún no hay entregables</strong>El historial aparecerá cuando el empleado envíe la primera versión.</div>
                @endforelse
            </section>
        </main>

        <aside class="delivery-aside">
            <section class="aside-card"><h3>Controles de seguridad</h3><ul class="check-list"><li>Archivos fuera del directorio público.</li><li>Descarga autorizada por relación laboral.</li><li>Nombre interno aleatorio y checksum SHA-256.</li><li>Máximo 8 archivos y 25 MB por archivo.</li></ul></section>
            <section class="aside-card"><h3>Formatos admitidos</h3><div class="format-cloud"><span>PDF</span><span>DOCX</span><span>XLSX</span><span>PPTX</span><span>ODF</span><span>CSV/JSON/XML</span><span>ZIP</span><span>PNG/JPG</span></div><p>Los formatos ejecutables y documentos con macros quedan bloqueados.</p></section>
        </aside>
    </div>
@endsection
