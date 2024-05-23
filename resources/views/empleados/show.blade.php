@extends('layouts.app-master')

@section('content')
<div class="container mt-4">
    <h1>Detalle del Empleado</h1>

    <div class="card">
        <div class="card-header">
            <h3>{{ $empleado->first_name }} {{ $empleado->last_name }}</h3>
        </div>
        <div class="card-body">
            <p><strong>Correo Electrónico:</strong> {{ $empleado->email }}</p>
            <p><strong>Departamento:</strong> {{ $empleado->departamento->nombre }}</p>
        </div>
        <div class="card-footer">
            <a href="{{ route('empleados.edit', $empleado) }}" class="btn btn-warning">Editar</a>
            <form action="{{ route('empleados.destroy', $empleado) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </form>
        </div>
    </div>
</div>
@endsection
