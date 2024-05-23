@extends('layouts.app-master')

@section('content')
    <h1 class="container mt-5">Home</h1>

    @auth
            <p>Bienvenido {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}, estás autenticado a la página</p>
            <p>
                <a href="{{ route('departamentos.index') }}" class="btn btn-primary">Gestionar Departamentos</a>
                <a href="{{ route('empleados.index') }}" class="btn btn-primary">Gestionar Empleados</a>
            </p>
            <p>
                <form action="/logout" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger">Logout</button>
                </form>
            </p>
    @endauth

    @guest
        <p>Para ver el contenido <a href="/login">Inicia sesión</a></p>
    @endguest
@endsection




