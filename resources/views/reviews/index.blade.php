@extends('layouts.app-master')

@section('title', 'Evaluaciones')
@section('eyebrow', 'Desempeño')
@section('page-title', 'Evaluaciones')

@section('content')
    @if(auth('admin')->check())
        <section class="panel form-panel reveal-item">
            <form method="POST" action="{{ route('reviews.cycles.store') }}" class="form-grid">
                @csrf
                <div class="field">
                    <label for="cycle-name">Nuevo ciclo</label>
                    <input class="input" id="cycle-name" name="name" placeholder="Evaluación semestral 2026" required>
                </div>
                <div class="field">
                    <label for="cycle-type">Tipo</label>
                    <select class="select" id="cycle-type" name="type">
                        <option value="manager">Evaluación del líder</option>
                        <option value="probation">Periodo de prueba</option>
                        <option value="annual">Anual</option>
                    </select>
                </div>
                <div class="field">
                    <label for="starts_at">Inicio</label>
                    <input class="input" id="starts_at" name="starts_at" type="date" required>
                </div>
                <div class="field">
                    <label for="ends_at">Cierre</label>
                    <input class="input" id="ends_at" name="ends_at" type="date" required>
                </div>
                <div class="field-full">
                    <button class="button button-primary">Activar para toda la organización</button>
                </div>
            </form>
        </section>

        <section class="cycle-strip reveal-item">
            @forelse($cycles as $cycle)
                <article>
                    <span class="status-chip status-{{ $cycle->status === 'active' ? 'approved' : 'pending' }}">{{ $cycle->status }}</span>
                    <h3>{{ $cycle->name }}</h3>
                    <p>{{ $cycle->submitted_count }}/{{ $cycle->reviews_count }} publicadas</p>
                    <progress class="progress" max="{{ max($cycle->reviews_count, 1) }}" value="{{ $cycle->submitted_count }}"></progress>
                </article>
            @empty
                <div class="empty-state">
                    <strong>Activa el primer ciclo</strong>
                    Define fechas y tipo de evaluación para preparar las revisiones del equipo.
                </div>
            @endforelse
        </section>
    @endif

    <section class="review-list reveal-item">
        @forelse($reviews as $review)
            <article class="panel review-card">
                <header>
                    <div>
                        <span class="eyebrow">{{ $review->cycle->name }}</span>
                        <h3>{{ $review->employee->full_name }}</h3>
                    </div>
                    <span class="status-chip status-{{ $review->status === 'acknowledged' ? 'approved' : ($review->status === 'submitted' ? 'in_progress' : 'pending') }}">
                        {{ $review->status }}
                    </span>
                </header>

                @if(auth('admin')->check() && $review->status === 'draft')
                    <form method="POST" action="{{ route('reviews.submit', $review) }}" class="compact-form">
                        @csrf
                        @method('PATCH')
                        <div class="score-pair">
                            <label>Desempeño <input class="input" name="performance_score" type="number" min="1" max="5" required></label>
                            <label>Potencial <input class="input" name="potential_score" type="number" min="1" max="5" required></label>
                        </div>
                        <label class="field">
                            <span>Síntesis basada en resultados observables</span>
                            <textarea class="textarea" name="summary" required></textarea>
                        </label>
                        <label class="field">
                            <span>Fortalezas</span>
                            <textarea class="textarea" name="strengths" required></textarea>
                        </label>
                        <label class="field">
                            <span>Áreas de desarrollo y acciones</span>
                            <textarea class="textarea" name="development_areas" required></textarea>
                        </label>
                        <button class="button button-primary">Publicar evaluación</button>
                    </form>
                @elseif($review->status !== 'draft')
                    <div class="review-evidence">
                        <div><span>Desempeño</span><strong>{{ $review->performance_score }}/5</strong></div>
                        <div><span>Potencial</span><strong>{{ $review->potential_score }}/5</strong></div>
                        <p><strong>Síntesis</strong>{{ $review->summary }}</p>
                        <p><strong>Fortalezas</strong>{{ $review->strengths }}</p>
                        <p><strong>Desarrollo</strong>{{ $review->development_areas }}</p>
                    </div>

                    @if(!auth('admin')->check() && $review->status === 'submitted')
                        <form method="POST" action="{{ route('reviews.acknowledge', $review) }}">
                            @csrf
                            <button class="button button-primary">Confirmar lectura y conversación</button>
                        </form>
                    @endif
                @endif
            </article>
        @empty
            <div class="empty-state">
                <strong>No hay evaluaciones disponibles</strong>
                @if(auth('admin')->check())
                    Activa un ciclo para generar las revisiones del equipo.
                @else
                    Cuando tu líder publique una evaluación aparecerá aquí.
                @endif
            </div>
        @endforelse
    </section>

    @include('layouts.partials.pagination', ['paginator' => $reviews])
@endsection
