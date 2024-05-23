@extends('layouts.auth-master')

@section('content')
    <form method="POST" action="{{ route('admin.register') }}">
        @csrf
        <h1>Registrar Administrador</h1>
        @include('layouts.partials.messages')
        <div class="form-floating mb-3">
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
            <label for="name">Nombre</label>
        </div>

        <div class="form-floating mb-3">
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="email" value="{{ old('email') }}" required autocomplete="email">
            <label for="email">Correo Electrónico</label>
        </div>

        <div class="form-floating mb-3">
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password" required autocomplete="new-password">
            <label for="password">Contraseña</label>
            <div id="passwordHelp" class="form-text">La contraseña debe tener al menos 8 caracteres</div>
        </div>

        <div class="form-floating mb-3">
            <input type="password" name="password_confirmation" class="form-control" id="password_confirmation" required autocomplete="new-password">
            <label for="password_confirmation">Confirmar Contraseña</label>
        </div>

        <button type="submit" class="btn btn-primary">Registrar</button>
    </form>
@endsection

