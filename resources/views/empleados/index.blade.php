@extends('layouts.app-master')

@section('content')
    <div class="container mt-5">
        <h1>Empleados</h1>
        <form method="GET" action="{{ route('empleados.index') }}">
            <div class="input-group mb-3">
                <input type="text" name="search" class="form-control" placeholder="Buscar empleados..." value="{{ request('search') }}">
                <button class="btn btn-outline-secondary" type="submit">Buscar</button>
            </div>
        </form>
        @if (Auth::guard('admin')->check())
            <a href="{{ route('empleados.create') }}" class="btn btn-primary">Agregar Empleado</a>
        @endif
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Departamento</th>
                    @if (Auth::guard('admin')->check())
                        <th>Acciones</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($empleados as $empleado)
                    <tr>
                        <td>{{ $empleado->id }}</td>
                        <td>{{ $empleado->first_name }} {{ $empleado->last_name }}</td>
                        <td>{{ $empleado->email }}</td>
                        <td>{{ $empleado->username }}</td>
                        <td>{{ $empleado->departamento ? $empleado->departamento->nombre : 'N/A' }}</td>
                        @if (Auth::guard('admin')->check())
                            <td>
                                <a href="{{ route('empleados.edit', $empleado) }}" class="btn btn-warning">Editar</a>
                                <form action="{{ route('empleados.destroy', $empleado) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Eliminar</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $empleados->links() }}
    </div>
@endsection







