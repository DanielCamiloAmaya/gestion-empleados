@extends('control-center.layouts.auth')
@section('title', 'Activar empresa')
@section('content')
<span class="cc-overline">Propietario designado · {{ $invitation->organization->name }}</span>
<h2>Toma control de tu espacio</h2>
<p class="cc-auth-intro">Crearás tu propia contraseña. PeopleOS no puede verla y no ha creado una por ti.</p>
<div class="cc-invitation-facts"><span><small>Workspace</small><strong>{{ $invitation->organization->slug }}</strong></span><span><small>Correo verificado</small><strong>{{ $invitation->email }}</strong></span><span><small>Vigencia</small><strong>{{ $invitation->expires_at->diffForHumans() }}</strong></span></div>
<form method="POST" action="{{ route('organization-owner-invitations.accept',$token) }}" class="cc-form">@csrf
    <label>Nueva contraseña<input name="password" type="password" required autocomplete="new-password"></label><label>Confirmar contraseña<input name="password_confirmation" type="password" required autocomplete="new-password"></label>
    <label class="cc-check"><input type="checkbox" name="terms" value="1" required><span>Acepto la responsabilidad de propietario, los términos del servicio y el tratamiento de datos aplicable.</span></label>
    <small>Usa al menos 12 caracteres, mayúsculas, minúsculas, números y símbolos.</small>
    <button class="cc-button cc-button-primary">Activar cuenta propietaria</button>
</form>
@endsection
