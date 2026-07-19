@extends('control-center.layouts.auth')
@section('title', 'Verificar identidad')
@section('content')
<span class="cc-overline">Segundo factor</span>
<h2>Confirma tu identidad</h2>
<p class="cc-auth-intro">Ingresa el código actual de tu autenticador o un código de recuperación de un solo uso.</p>
<form method="POST" action="{{ route('control.mfa.verify') }}" class="cc-form">
    @csrf
    <label>Código de autenticación<input name="code" autocomplete="one-time-code" maxlength="32" required autofocus placeholder="000000"></label>
    <button class="cc-button cc-button-primary" type="submit">Verificar y continuar</button>
</form>
<form method="POST" action="{{ route('control.logout') }}" class="cc-inline-form">@csrf<button class="cc-link" type="submit">Cerrar sesión</button></form>
@endsection
