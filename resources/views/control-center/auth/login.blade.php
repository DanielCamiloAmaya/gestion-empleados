@extends('control-center.layouts.auth')
@section('title', 'Acceso interno')
@section('content')
<span class="cc-overline">Identidad de plataforma</span>
<h2>Acceso al Control Center</h2>
<p class="cc-auth-intro">Solo para personal autorizado de PeopleOS. Todas las acciones se registran con identidad individual.</p>
<form method="POST" action="{{ route('control.login.store') }}" class="cc-form">
    @csrf
    <label>Correo corporativo<input name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus placeholder="nombre@peopleos.com"></label>
    <label>Contraseña<input name="password" type="password" required autocomplete="current-password"></label>
    <button class="cc-button cc-button-primary" type="submit">Continuar con MFA <span>→</span></button>
</form>
<p class="cc-auth-note">¿Buscas administrar una empresa? Usa el <a href="{{ route('admin.login') }}">portal de administradores</a>.</p>
@endsection
