<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#10231f">
    <title>@yield('title', 'PeopleOS') · PeopleOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body">
    <div class="app-shell">
        @include('layouts.partials.navbar')

        <main class="app-main" id="main-content">
            <header class="topbar">
                <button class="mobile-menu" type="button" data-menu-toggle aria-label="Abrir navegación" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <p class="eyebrow">@yield('eyebrow', 'People operations')</p>
                    <h1>@yield('page-title', 'Panel')</h1>
                </div>
                <div class="topbar-user">
                    @php($unreadNotifications = (auth('admin')->user() ?? auth()->user())->unreadNotifications()->count())
                    <a class="notification-button" href="{{ route('notifications.index') }}" aria-label="Notificaciones{{ $unreadNotifications ? ': '.$unreadNotifications.' sin leer' : '' }}">
                        <span aria-hidden="true">○</span>@if($unreadNotifications)<strong>{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</strong>@endif
                    </a>
                    <span class="status-dot" aria-hidden="true"></span>
                    <div>
                        <strong>{{ auth('admin')->user()?->name ?? auth()->user()?->full_name }}</strong>
                        <small>{{ auth('admin')->check() ? 'Recursos Humanos' : 'Portal del empleado' }}</small>
                    </div>
                </div>
            </header>

            <div class="page-content">
                @include('layouts.partials.messages')
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
