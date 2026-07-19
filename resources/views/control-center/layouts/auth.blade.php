<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#071827">
    <title>@yield('title', 'Acceso interno') · PeopleOS Control Center</title>
    @vite(['resources/css/control-center.css', 'resources/js/app.js'])
</head>
<body class="cc-auth-body">
    <main class="cc-auth-shell">
        <section class="cc-auth-context">
            <a class="cc-brand cc-brand-light" href="{{ route('control.login') }}">
                <span class="cc-brand-mark">P</span>
                <span><strong>PeopleOS</strong><small>Control Center</small></span>
            </a>
            <div class="cc-auth-message">
                <span class="cc-overline">Control plane · acceso restringido</span>
                <h1>La confianza también necesita límites.</h1>
                <p>Operación multiempresa con identidad independiente, mínimo privilegio y trazabilidad verificable.</p>
            </div>
            <div class="cc-auth-rail" aria-label="Controles activos">
                <span><i></i>MFA obligatorio</span>
                <span><i></i>Sesión efímera</span>
                <span><i></i>Auditoría inmutable</span>
            </div>
        </section>
        <section class="cc-auth-panel">
            <div class="cc-auth-card">
                @include('layouts.partials.messages')
                @yield('content')
            </div>
        </section>
    </main>
</body>
</html>
