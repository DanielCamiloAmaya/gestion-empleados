@extends('layouts.auth-master')

@section('content')
    <form action="{{ route('admin.login') }}" method="POST" class="container">
        @csrf
        <h1>Login de Administrador</h1>
        @include('layouts.partials.messages')
        <div class="form-floating mb-3">
            <input type="text" placeholder="username" name="name" class="form-control" id="nameInput" aria-describedby="nameHelp">
            <label for="nameInput" class="form-label">Nombre o Email</label>
        </div>
        <div class="form-floating mb-3">
            <input type="password" placeholder="password" name="password" class="form-control" id="passwordInput">
            <label for="passwordInput" class="form-label">Contraseña</label>
        </div>
        <div class="mb-3">
            <a href="/admin/register">Crear cuenta</a>
        </div>
        <button type="submit" class="btn btn-primary">Enviar</button>
    </form>
@endsection

