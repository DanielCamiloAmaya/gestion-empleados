@extends('layouts.app-master')
@section('title', 'Departamentos')
@section('eyebrow', 'Diseño organizacional')
@section('page-title', 'Departamentos')
@section('content')
    <div class="page-heading">
        <div><h2>Estructura organizacional</h2><p>Áreas, centros de costo y tamaño actual de cada equipo.</p></div>
        @if(auth('admin')->check())<a class="button button-primary" href="{{ route('departamentos.create') }}"><span>＋</span> Nuevo departamento</a>@endif
    </div>
    <form class="filter-bar filter-bar-compact" method="GET" action="{{ route('departamentos.index') }}"><input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Buscar departamento…" aria-label="Buscar departamentos"><button class="button button-secondary" type="submit">Buscar</button></form>
    <section class="department-card-grid">
        @forelse($departamentos as $departamento)
            <article class="department-card">
                <div class="department-card-top"><span class="department-icon">{{ mb_strtoupper(mb_substr($departamento->nombre, 0, 1)) }}</span><span class="badge badge-{{ $departamento->is_active ? 'active' : 'inactive' }}">{{ $departamento->is_active ? 'Activo' : 'Inactivo' }}</span></div>
                <h3>{{ $departamento->nombre }}</h3>
                <p>{{ $departamento->description ?: 'Área organizacional sin descripción registrada.' }}</p>
                <div class="department-meta"><span><strong>{{ $departamento->empleados_count }}</strong> personas</span><span>{{ $departamento->cost_center ?: 'Sin centro de costo' }}</span></div>
                @if(auth('admin')->check())
                    <div class="actions department-actions"><a class="button button-secondary button-small" href="{{ route('departamentos.edit', $departamento) }}">Editar</a><form method="POST" action="{{ route('departamentos.destroy', $departamento) }}" data-confirm="¿Eliminar este departamento? Esta acción solo funcionará si no tiene personas asociadas.">@csrf @method('DELETE')<button class="button button-danger button-small" type="submit">Eliminar</button></form></div>
                @endif
            </article>
        @empty
            <div class="panel empty-state"><strong>No encontramos departamentos</strong>Crea la estructura inicial de la organización.</div>
        @endforelse
    </section>
    @include('layouts.partials.pagination', ['paginator' => $departamentos])
@endsection
