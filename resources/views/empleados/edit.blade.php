@extends('layouts.app-master')

@section('content')
    <div class="container">
        <h1>Editar Empleado</h1>
        <form method="POST" action="{{ route('empleados.update', $empleado) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="first_name" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $empleado->first_name) }}" required>
            </div>
            <div class="mb-3">
                <label for="last_name" class="form-label">Apellido</label>
                <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $empleado->last_name) }}" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $empleado->email) }}" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="{{ old('username', $empleado->username) }}" required>
            </div>
            <div class="mb-3">
                <label for="departamento_id" class="form-label">Departamento</label>
                <select class="form-select" id="departamento_id" name="departamento_id" required>
                    @foreach($departamentos as $departamento)
                        <option value="{{ $departamento->id }}" {{ $empleado->departamento_id == $departamento->id ? 'selected' : '' }}>{{ $departamento->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña (dejar en blanco para mantener la actual)</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
            </div>
            <button type="submit" class="btn btn-primary">Actualizar</button>
        </form>
    </div>
@endsection



