@extends('layouts.app-master')

@section('content')
    <div class="container mt-5">
        <h1>Departamentos</h1>
        @if (Auth::guard('admin')->check())
        <a href="{{ route('departamentos.create') }}" class="btn btn-primary mt-3 mb-3">Agregar Departamento</a>
        @endif
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    @if (Auth::guard('admin')->check())
                    <th>Acciones</th>
                @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($departamentos as $departamento)
                    <tr>
                        <td>{{ $departamento->id }}</td>
                        <td>{{ $departamento->nombre }}</td>
                        @if (Auth::guard('admin')->check())
                        <td>
                            <a href="{{ route('departamentos.edit', $departamento) }}" class="btn btn-warning">Editar</a>
                            <form action="{{ route('departamentos.destroy', $departamento) }}" method="POST" style="display:inline;">
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
        {{ $departamentos->links() }}
    </div>
@endsection
