@extends('control-center.layouts.auth')
@section('title', 'Activar MFA')
@section('content')
<span class="cc-overline">Protección obligatoria</span>
<h2>Vincula tu autenticador</h2>
<p class="cc-auth-intro">Agrega esta clave a Microsoft Authenticator, 1Password, Google Authenticator o cualquier aplicación TOTP.</p>
<button type="button" class="cc-secret" data-copy="{{ $secret }}"><span>Clave de configuración</span><code>{{ $secret }}</code><small>Copiar</small></button>
<details class="cc-details"><summary>Ver URI de aprovisionamiento</summary><code>{{ $uri }}</code></details>
<form method="POST" action="{{ route('control.mfa.enable') }}" class="cc-form">
    @csrf
    <label>Código de seis dígitos<input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required placeholder="000000"></label>
    <button class="cc-button cc-button-primary" type="submit">Activar MFA y entrar</button>
</form>
@endsection
