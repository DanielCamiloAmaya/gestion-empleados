@extends('layouts.app-master')

@section('content')
    <div class="container mt-5">
        <h1>Agregar Departamento</h1>

        <form method="POST" action="{{ route('departamentos.store') }}">
            @csrf

            <div class="form-group mt-3">
                <label for="nombre">Nombre del Departamento</label>
                <input type="text" class="form-control mt-3" id="nombre" name="nombre" value="{{ old('nombre') }}" required>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Crear Departamento</button>
        </form>
    </div>
@endsection
