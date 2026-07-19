@extends('layouts.app-master')

@section('title', 'Roles y permisos')
@section('eyebrow', 'Mínimo privilegio')
@section('page-title', 'Acceso administrativo')

@section('content')
    <section class="access-hero reveal-item">
        <div>
            <span class="eyebrow">RBAC por organización</span>
            <h2>Una identidad por persona. Un permiso por necesidad.</h2>
            <p>Invita administradores sin compartir contraseñas, asigna roles explícitos y revoca inmediatamente sus sesiones y tokens.</p>
        </div>
        <strong>{{ $permissions->flatten()->count() }}<small>capacidades</small></strong>
    </section>

    <div class="platform-grid reveal-item">
        <section class="panel form-panel">
            <span class="eyebrow">Ciclo de vida</span>
            <h3>Invitar administrador</h3>
            <form method="POST" action="{{ route('access.admins.invite') }}" class="compact-form">
                @csrf
                <div class="field"><label for="admin-name">Nombre completo</label><input class="input" id="admin-name" name="name" required></div>
                <div class="field"><label for="admin-email">Correo corporativo</label><input class="input" id="admin-email" name="email" type="email" required></div>
                <div class="field"><label for="admin-role">Rol inicial</label><select class="select" id="admin-role" name="role_id" required>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select></div>
                <p class="field-hint">La persona crea su propia contraseña desde un enlace de uso único válido por 48 horas.</p>
                <button class="button button-primary">Enviar invitación segura</button>
            </form>
            @if(session('admin_invitation_url'))
                <div class="secret-reveal"><strong>Enlace local de prueba</strong><a href="{{ session('admin_invitation_url') }}">Abrir invitación</a></div>
            @endif
            @foreach($invitations as $invitation)
                <div class="connection-row"><span><strong>{{ $invitation->name }}</strong><small>{{ $invitation->email }} · vence {{ $invitation->expires_at->diffForHumans() }}</small></span><span class="status-chip status-pending">Pendiente</span></div>
            @endforeach
        </section>

        <section class="panel form-panel">
            <span class="eyebrow">Diseño de acceso</span>
            <h3>Crear rol personalizado</h3>
            <form method="POST" action="{{ route('access.roles.store') }}" class="compact-form">
                @csrf
                <div class="field"><label for="role-name">Nombre del rol</label><input class="input" id="role-name" name="name" required placeholder="Analista de talento"></div>
                <div class="field"><label for="role-description">Responsabilidad y límites</label><textarea class="textarea" id="role-description" name="description"></textarea></div>
                <div class="permission-matrix">
                    @foreach($permissions as $module => $items)
                        <fieldset>
                            <legend>{{ $module }}</legend>
                            @foreach($items as $permission)
                                <label class="checkbox"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}"> <span><strong>{{ $permission->name }}</strong><small>{{ $permission->description }}</small></span></label>
                            @endforeach
                        </fieldset>
                    @endforeach
                </div>
                <button class="button button-primary">Crear rol</button>
            </form>
        </section>
    </div>

    <section class="panel reveal-item">
        <header class="panel-header"><div><h3>Administradores de la empresa</h3><p>Asignaciones, estado y revocación inmediata.</p></div></header>
        <div class="assignment-list">
            @foreach($admins as $admin)
                <article class="admin-access-row">
                    <form method="POST" action="{{ route('access.assign', $admin) }}">
                        @csrf
                        @method('PUT')
                        <span><strong>{{ $admin->name }}</strong><small>{{ $admin->email }} · {{ $admin->status }}</small></span>
                        <div>
                            @foreach($roles as $role)
                                <label class="checkbox"><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($admin->roles->contains($role)) @disabled($admin->role === 'owner' || $admin->status !== 'active')> {{ $role->name }}</label>
                            @endforeach
                        </div>
                        @if($admin->role !== 'owner' && $admin->status === 'active')<button class="button button-secondary button-small">Guardar permisos</button>@else<span class="status-chip status-{{ $admin->status === 'active' ? 'approved' : 'pending' }}">{{ $admin->role === 'owner' ? 'Propietario protegido' : 'Deshabilitado' }}</span>@endif
                    </form>
                    @if(!$admin->is(auth('admin')->user()))
                        @if($admin->status === 'active')
                            <form method="POST" action="{{ route('access.admins.disable', $admin) }}" data-confirm="Se cerrarán sus sesiones y se revocarán sus tokens.">@csrf @method('DELETE')<button class="button button-danger button-small">Deshabilitar</button></form>
                        @else
                            <form method="POST" action="{{ route('access.admins.enable', $admin) }}">@csrf @method('PATCH')<button class="button button-secondary button-small">Reactivar</button></form>
                        @endif
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <section class="panel form-panel reveal-item">
        <span class="eyebrow">Política de autenticación</span>
        <h3>MFA obligatorio por población</h3>
        <form method="POST" action="{{ route('access.mfa-policy') }}" class="compact-form">
            @csrf @method('PUT')
            <label class="checkbox"><input type="checkbox" name="require_admin_mfa" value="1" @checked(auth('admin')->user()->organization->settings['require_admin_mfa'] ?? false)> Exigir MFA a todos los administradores</label>
            <label class="checkbox"><input type="checkbox" name="require_employee_mfa" value="1" @checked(auth('admin')->user()->organization->settings['require_employee_mfa'] ?? false)> Exigir MFA a todos los empleados</label>
            <button class="button button-primary">Guardar política MFA</button>
        </form>
    </section>

    <section class="panel reveal-item">
        <header class="panel-header"><div><h3>Catálogo de roles</h3><p>Capacidad y cobertura actual.</p></div></header>
        <div class="role-list">@foreach($roles as $role)<article><div><span class="status-chip {{ $role->is_system ? 'status-approved' : 'status-pending' }}">{{ $role->is_system ? 'Sistema' : 'Personalizado' }}</span><h3>{{ $role->name }}</h3><p>{{ $role->description }}</p></div><span><strong>{{ $role->permissions->count() }}</strong><small>permisos</small><strong>{{ $role->admins->count() }}</strong><small>usuarios</small></span></article>@endforeach</div>
    </section>
@endsection
