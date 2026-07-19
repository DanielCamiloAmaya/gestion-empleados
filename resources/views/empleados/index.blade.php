@extends('layouts.app-master')

@section('title', 'Directorio de personas')
@section('eyebrow', 'Organización')
@section('page-title', 'Directorio de personas')

@section('content')
    <div class="page-heading">
        <div><h2>Personas</h2><p>Una vista confiable de quién es quién, su rol y el lugar que ocupa en la organización.</p></div>
        @if(auth('admin')->check())<div class="actions"><a class="button button-secondary" href="{{ route('employees.import.create') }}">Importar CSV</a><a class="button button-secondary" href="{{ route('employees.export') }}">Exportar</a><a class="button button-primary" href="{{ route('empleados.create') }}"><span>＋</span> Incorporar persona</a></div>@endif
    </div>

    <form class="filter-bar" method="GET" action="{{ route('empleados.index') }}">
        <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, cargo o código…" aria-label="Buscar personas">
        <select class="select" name="department" aria-label="Filtrar por departamento">
            <option value="">Todos los departamentos</option>
            @foreach($departamentos as $department)<option value="{{ $department->id }}" @selected((string) request('department') === (string) $department->id)>{{ $department->nombre }}</option>@endforeach
        </select>
        <select class="select" name="status" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            @foreach(['active', 'onboarding', 'leave', 'inactive'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ __('status.'.$status) }}</option>@endforeach
        </select>
        <button class="button button-secondary" type="submit">Filtrar</button>
    </form>

    <section class="panel">
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Persona</th><th>Cargo</th><th>Departamento</th><th>Estado</th>@if(auth('admin')->check())<th>Contacto</th><th><span class="visually-hidden">Acciones</span></th>@endif</tr></thead>
                <tbody>
                    @forelse($empleados as $empleado)
                        <tr>
                            <td><a class="person" href="{{ route('empleados.show', $empleado) }}"><span class="avatar">{{ $empleado->initials }}</span><span><strong>{{ $empleado->full_name }}</strong><small>{{ $empleado->employee_code ?: 'Código pendiente' }}</small></span></a></td>
                            <td>{{ $empleado->job_title ?: 'Por definir' }}</td>
                            <td><span class="subtle">{{ $empleado->departamento?->nombre ?? 'Sin asignar' }}</span></td>
                            <td><span class="badge badge-{{ $empleado->employment_status }}">{{ __('status.'.$empleado->employment_status) }}</span></td>
                            @if(auth('admin')->check())
                                <td><span class="subtle">{{ $empleado->email }}</span></td>
                                <td><div class="row-actions"><a class="button button-secondary button-small" href="{{ route('empleados.edit', $empleado) }}">Editar</a></div></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><strong>No encontramos personas</strong>Prueba otros filtros o incorpora un nuevo empleado.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('layouts.partials.pagination', ['paginator' => $empleados])
    </section>
@endsection
