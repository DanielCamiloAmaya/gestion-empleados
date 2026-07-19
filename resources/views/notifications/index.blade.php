@extends('layouts.app-master')
@section('title', 'Notificaciones')
@section('eyebrow', 'Centro de actividad')
@section('page-title', 'Notificaciones')
@section('content')
    <div class="page-heading reveal-item">
        <div><h2>Lo importante, en un solo lugar</h2><p>Decisiones, cambios y próximos pasos de tu espacio de trabajo.</p></div>
        @if($unreadCount)<form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="button button-secondary" type="submit">Marcar todo como leído</button></form>@endif
    </div>
    <section class="notification-ledger reveal-item" aria-label="Historial de notificaciones">
        @forelse($notifications as $notification)
            <a class="notification-row {{ $notification->read_at ? '' : 'is-unread' }}" href="{{ route('notifications.read', $notification->id) }}">
                <span class="notification-signal signal-{{ $notification->data['severity'] ?? 'info' }}" aria-hidden="true"></span>
                <span class="notification-copy"><strong>{{ $notification->data['title'] }}</strong><span>{{ $notification->data['body'] }}</span></span>
                <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time><span aria-hidden="true">→</span>
            </a>
        @empty<div class="empty-state"><strong>Todo está al día</strong>Las decisiones y cambios relevantes aparecerán aquí.</div>@endforelse
    </section>
    @include('layouts.partials.pagination', ['paginator' => $notifications])
@endsection
