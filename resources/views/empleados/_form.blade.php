@php($editing = isset($empleado))
<div class="form-section">
    <h3>Identidad laboral</h3>
    <p>Información principal con la que esta persona será identificada en la organización.</p>
    <div class="form-grid">
        <div class="field"><label for="employee_code">Código de empleado</label><input class="input" id="employee_code" name="employee_code" value="{{ old('employee_code', $empleado->employee_code ?? '') }}" required maxlength="30" placeholder="EMP-001"></div>
        <div class="field"><label for="job_title">Cargo</label><input class="input" id="job_title" name="job_title" value="{{ old('job_title', $empleado->job_title ?? '') }}" required maxlength="150" placeholder="Product Designer"></div>
        <div class="field"><label for="first_name">Nombres</label><input class="input" id="first_name" name="first_name" value="{{ old('first_name', $empleado->first_name ?? '') }}" required maxlength="100" autocomplete="given-name"></div>
        <div class="field"><label for="last_name">Apellidos</label><input class="input" id="last_name" name="last_name" value="{{ old('last_name', $empleado->last_name ?? '') }}" required maxlength="100" autocomplete="family-name"></div>
        <div class="field"><label for="email">Correo corporativo</label><input class="input" id="email" type="email" name="email" value="{{ old('email', $empleado->email ?? '') }}" required autocomplete="email" placeholder="persona@empresa.com"></div>
        <div class="field"><label for="username">Nombre de usuario</label><input class="input" id="username" name="username" value="{{ old('username', $empleado->username ?? '') }}" required maxlength="80" autocomplete="username" placeholder="nombre.apellido"></div>
    </div>
</div>

<div class="form-section">
    <h3>Posición y estructura</h3>
    <p>Define las relaciones organizacionales y las condiciones vigentes.</p>
    <div class="form-grid">
        <div class="field"><label for="departamento_id">Departamento</label><select class="select" id="departamento_id" name="departamento_id" required><option value="">Selecciona un área</option>@foreach($departamentos as $department)<option value="{{ $department->id }}" @selected((string) old('departamento_id', $empleado->departamento_id ?? '') === (string) $department->id)>{{ $department->nombre }}</option>@endforeach</select></div>
        <div class="field"><label for="manager_id">Jefe directo</label><select class="select" id="manager_id" name="manager_id"><option value="">Sin jefe asignado</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected((string) old('manager_id', $empleado->manager_id ?? '') === (string) $manager->id)>{{ $manager->full_name }} · {{ $manager->job_title }}</option>@endforeach</select></div>
        <div class="field"><label for="employment_status">Estado laboral</label><select class="select" id="employment_status" name="employment_status" required>@foreach(['onboarding','active','leave','inactive'] as $status)<option value="{{ $status }}" @selected(old('employment_status', $empleado->employment_status ?? 'onboarding') === $status)>{{ __('status.'.$status) }}</option>@endforeach</select></div>
        <div class="field"><label for="employment_type">Tipo de vinculación</label><select class="select" id="employment_type" name="employment_type" required>@foreach(['full_time','part_time','contractor','intern'] as $type)<option value="{{ $type }}" @selected(old('employment_type', $empleado->employment_type ?? 'full_time') === $type)>{{ __('employment.'.$type) }}</option>@endforeach</select></div>
        <div class="field"><label for="hire_date">Fecha de ingreso</label><input class="input" id="hire_date" type="date" name="hire_date" value="{{ old('hire_date', isset($empleado) ? $empleado->hire_date?->format('Y-m-d') : now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required></div>
        <div class="field"><label for="location">Sede o ubicación</label><input class="input" id="location" name="location" value="{{ old('location', $empleado->location ?? '') }}" maxlength="150" placeholder="Bogotá · Híbrido"></div>
        <div class="field field-full"><label for="phone">Teléfono</label><input class="input" id="phone" name="phone" value="{{ old('phone', $empleado->phone ?? '') }}" maxlength="30" autocomplete="tel" placeholder="+57 300 000 0000"></div>
    </div>
</div>

<div class="form-section">
    <h3>{{ $editing ? 'Actualizar acceso' : 'Acceso inicial' }}</h3>
    <p>{{ $editing ? 'Deja estos campos vacíos para conservar la contraseña actual.' : 'Entrega la contraseña mediante un canal seguro y solicita cambiarla en el primer acceso.' }}</p>
    <div class="form-grid">
        <div class="field"><label for="password">Contraseña {{ $editing ? 'nueva' : 'temporal' }}</label><input class="input" id="password" type="password" name="password" autocomplete="new-password" @required(!$editing)><span class="field-hint">Mínimo 12 caracteres, mayúsculas, minúsculas, números y símbolos.</span></div>
        <div class="field"><label for="password_confirmation">Confirmar contraseña</label><input class="input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" @required(!$editing)></div>
    </div>
</div>
