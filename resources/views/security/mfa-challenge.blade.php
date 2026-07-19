@extends('layouts.auth-master')

@section('title', 'Verificacion en dos pasos')

@section('content')
    <span class="auth-kicker">Segundo factor</span>
    <h2>Confirma que eres tú</h2>
    <p class="auth-intro">Ingresa el código de seis dígitos de tu aplicación autenticadora. También puedes usar uno de tus códigos de recuperación.</p>

    <form action="{{ route('mfa.verify') }}" method="POST" class="auth-form">
        @csrf
        <div class="field">
            <label for="code">Código de autenticación</label>
            <input class="input input-code" id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="32" required autofocus placeholder="000000">
        </div>
        <button type="submit" class="button button-primary">Verificar identidad <span>→</span></button>
    </form>

    <form method="POST" action="{{ auth('admin')->check() ? route('admin.logout') : route('logout') }}" class="auth-switch">
        @csrf
        <button class="link-button" type="submit">Cerrar sesión y volver</button>
    </form>
@endsection
