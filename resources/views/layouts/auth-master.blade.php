<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#10231f">
    <title>@yield('title', 'Acceso') · PeopleOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-story" aria-label="PeopleOS">
            <a class="brand brand-light" href="{{ url('/') }}">
                <span class="brand-mark">P</span>
                <span><strong>PeopleOS</strong><small>Human Capital</small></span>
            </a>
            <div class="auth-story-content">
                <span class="story-pill">People intelligence, beautifully simple</span>
                <h1>El talento mueve tu empresa. Nosotros hacemos visible su potencial.</h1>
                <p>Una experiencia segura y humana para conectar personas, estructura y decisiones.</p>
                <div class="story-metrics">
                    <div><strong>1:1</strong><span>Evaluación trazable</span></div>
                    <div><strong>24/7</strong><span>Acceso protegido</span></div>
                    <div><strong>1</strong><span>Fuente de verdad</span></div>
                </div>
            </div>
            <p class="story-footer">Diseñado para organizaciones que ponen a las personas primero.</p>
        </section>

        <section class="auth-panel">
            <div class="auth-card">
                @include('layouts.partials.messages')
                @yield('content')
            </div>
        </section>
    </main>
</body>
</html>
