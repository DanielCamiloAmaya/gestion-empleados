@extends('control-center.layouts.auth')
@section('title', 'Activar cuenta interna')
@section('content')
<span class="cc-overline">Invitación interna · {{ str_replace('_', ' ', $user->role) }}</span>
<h2>Activa tu identidad, {{ Str::before($user->name, ' ') }}</h2>
<p class="cc-auth-intro">Esta cuenta es personal. Después de definir tu contraseña deberás configurar MFA antes de acceder.</p>
<form method="POST" action="{{ route('control.invitation.accept', $token) }}" class="cc-form">
    @csrf
    <label>Nueva contraseña<input name="password" type="password" required autocomplete="new-password"></label>
    <label>Confirmar contraseña<input name="password_confirmation" type="password" required autocomplete="new-password"></label>
    <small>Usa al menos 12 caracteres, mayúsculas, minúsculas, números y símbolos.</small>
    <button class="cc-button cc-button-primary" type="submit">Activar cuenta interna</button>
</form>
@endsection
