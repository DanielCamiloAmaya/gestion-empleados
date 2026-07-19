<div class="form-section">
    <h3>Identidad del área</h3><p>Usa nombres reconocibles para toda la organización.</p>
    <div class="form-grid">
        <div class="field"><label for="nombre">Nombre</label><input class="input" id="nombre" name="nombre" value="{{ old('nombre', $departamento->nombre ?? '') }}" required maxlength="120" placeholder="Producto y Tecnología"></div>
        <div class="field"><label for="cost_center">Centro de costo</label><input class="input" id="cost_center" name="cost_center" value="{{ old('cost_center', $departamento->cost_center ?? '') }}" maxlength="50" placeholder="CC-1002"></div>
        <div class="field field-full"><label for="description">Descripción</label><textarea class="textarea" id="description" name="description" maxlength="1000" placeholder="Propósito y responsabilidades principales del área…">{{ old('description', $departamento->description ?? '') }}</textarea></div>
        <div class="field field-full"><label for="is_active">Estado</label><select class="select" id="is_active" name="is_active" required><option value="1" @selected((string) old('is_active', isset($departamento) ? (int) $departamento->is_active : 1) === '1')>Activo</option><option value="0" @selected((string) old('is_active', isset($departamento) ? (int) $departamento->is_active : 1) === '0')>Inactivo</option></select><span class="field-hint">Los departamentos inactivos no aparecerán al incorporar personas.</span></div>
    </div>
</div>
