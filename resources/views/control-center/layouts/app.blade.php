<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#071827">
    <title>@yield('title', 'Control Center') · PeopleOS</title>
    @vite(['resources/css/control-center.css', 'resources/js/app.js'])
</head>
<body class="cc-body">
<div class="cc-shell">
    <aside class="cc-sidebar" data-sidebar>
        <a class="cc-brand cc-brand-light" href="{{ route('control.dashboard') }}">
            <span class="cc-brand-mark">P</span>
            <span><strong>PeopleOS</strong><small>Control Center</small></span>
        </a>
        <nav class="cc-nav" aria-label="Navegación del Control Center">
            <span class="cc-nav-label">Control plane</span>
            <a href="{{ route('control.dashboard') }}" @class(['active' => request()->routeIs('control.dashboard')])><span>◫</span>Empresas</a>
            @if(auth('platform')->user()->hasPermission('platform_users.manage'))
                <a href="{{ route('control.users.index') }}" @class(['active' => request()->routeIs('control.users.*')])><span>◎</span>Equipo interno</a>
            @endif
            @if(auth('platform')->user()->hasPermission('audit.view'))
                <a href="{{ route('control.audit') }}" @class(['active' => request()->routeIs('control.audit')])><span>◇</span>Auditoría</a>
            @endif
        </nav>
        <div class="cc-sidebar-foot">
            <div class="cc-trust-badge"><span></span><div><strong>Control plane aislado</strong><small>Sin acceso tenant implícito</small></div></div>
            <form method="POST" action="{{ route('control.logout') }}">@csrf
                <button type="submit">Cerrar sesión</button>
            </form>
        </div>
    </aside>
    <main class="cc-main">
        <header class="cc-topbar">
            <button class="cc-menu" type="button" data-menu-toggle aria-label="Abrir navegación" aria-expanded="false"><span></span><span></span><span></span></button>
            <div><span class="cc-overline">@yield('eyebrow', 'Operación de plataforma')</span><h1>@yield('page-title', 'Control Center')</h1></div>
            <div class="cc-operator"><span>{{ Str::upper(Str::substr(auth('platform')->user()->name, 0, 1)) }}</span><div><strong>{{ auth('platform')->user()->name }}</strong><small>{{ str_replace('_', ' ', auth('platform')->user()->role) }} · MFA</small></div></div>
        </header>
        <div class="cc-content">
            @include('layouts.partials.messages')
            @yield('content')
        </div>
    </main>
</div>
<button class="cc-backdrop" type="button" data-menu-toggle aria-label="Cerrar navegación"></button>
</body>
</html>
