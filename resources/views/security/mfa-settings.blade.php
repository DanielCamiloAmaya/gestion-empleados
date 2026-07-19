@extends('layouts.app-master')

@section('title', 'Seguridad de la cuenta')
@section('eyebrow', 'Identidad y acceso')
@section('page-title', 'Seguridad de la cuenta')

@section('content')
    <section class="security-hero reveal-item">
        <div>
            <span class="security-state {{ $actor->mfa_enabled ? 'is-enabled' : '' }}">{{ $actor->mfa_enabled ? 'MFA activo' : 'Protección pendiente' }}</span>
            <h2>Una segunda prueba para decisiones sensibles</h2>
            <p>PeopleOS admite aplicaciones compatibles con TOTP como Microsoft Authenticator, Google Authenticator, 1Password o Authy.</p>
        </div>
        <div class="security-orbit" aria-hidden="true"><span></span><strong>2FA</strong></div>
    </section>

    @if(session('recovery_codes'))
        <section class="panel recovery-panel reveal-item" aria-labelledby="recovery-title">
            <div class="section-heading"><div><span class="eyebrow">Uso único</span><h2 id="recovery-title">Códigos de recuperación</h2></div></div>
            <p>Guárdalos ahora en un gestor seguro. No volverán a mostrarse y cada código funciona una sola vez.</p>
            <div class="recovery-grid">
                @foreach(session('recovery_codes') as $code)<code>{{ $code }}</code>@endforeach
            </div>
        </section>
    @endif

    @if(! $actor->mfa_enabled)
        <section class="panel setup-grid reveal-item">
            <div>
                <span class="step-index">01</span>
                <h3>Agrega la cuenta</h3>
                <p>Copia esta clave en tu aplicación autenticadora usando la opción de configuración manual.</p>
                <button type="button" class="secret-box" data-copy="{{ $secret }}" aria-label="Copiar clave MFA"><code>{{ $secret }}</code><span>Copiar</span></button>
                <details class="uri-details"><summary>Ver URI de aprovisionamiento</summary><code>{{ $uri }}</code></details>
            </div>
            <form action="{{ route('mfa.enable') }}" method="POST" class="form-card compact-form">
                @csrf
                <span class="step-index">02</span>
                <h3>Verifica el primer código</h3>
                <div class="field"><label for="code">Código de seis dígitos</label><input class="input input-code" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required placeholder="000000"></div>
                <button class="button button-primary" type="submit">Activar autenticación</button>
            </form>
        </section>
    @else
        <section class="panel danger-zone reveal-item">
            <div><span class="eyebrow">Control de acceso</span><h2>Autenticación multifactor activa</h2><p>La cuenta está protegida desde {{ $actor->mfa_confirmed_at?->format('d/m/Y H:i') }}.</p></div>
            <details>
                <summary class="button button-secondary">Desactivar MFA</summary>
                <form action="{{ route('mfa.disable') }}" method="POST" class="compact-form">
                    @csrf @method('DELETE')
                    <div class="field"><label for="password">Contraseña actual</label><input class="input" id="password" name="password" type="password" required autocomplete="current-password"></div>
                    <div class="field"><label for="disable-code">Código actual</label><input class="input" id="disable-code" name="code" inputmode="numeric" maxlength="6" required></div>
                    <button class="button button-danger" type="submit">Confirmar desactivación</button>
                </form>
            </details>
        </section>
    @endif
@endsection
