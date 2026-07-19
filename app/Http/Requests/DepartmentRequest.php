<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        $department = $this->route('departamento');
        $organizationId = auth('admin')->user()->organization_id;

        return [
            'nombre' => ['required', 'string', 'max:120', Rule::unique('departamentos')->where(fn ($query) => $query->where('organization_id', $organizationId))->ignore($department)],
            'description' => ['nullable', 'string', 'max:1000'],
            'cost_center' => ['nullable', 'string', 'max:50', Rule::unique('departamentos')->where(fn ($query) => $query->where('organization_id', $organizationId))->ignore($department)],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
