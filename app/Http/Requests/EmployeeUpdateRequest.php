<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EmployeeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        $employee = $this->route('empleado');
        $organizationId = auth('admin')->user()->organization_id;

        return [
            'employee_code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('users')->where(fn ($query) => $query->where('organization_id', $organizationId))->ignore($employee)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')->where(fn ($query) => $query->where('organization_id', $organizationId))->ignore($employee)],
            'username' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users')->where(fn ($query) => $query->where('organization_id', $organizationId))->ignore($employee)],
            'departamento_id' => ['required', Rule::exists('departamentos', 'id')->where(fn ($query) => $query->where('organization_id', $organizationId))],
            'job_title' => ['required', 'string', 'max:150'],
            'employment_status' => ['required', Rule::in(['onboarding', 'active', 'leave', 'inactive'])],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contractor', 'intern'])],
            'hire_date' => ['required', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:150'],
            'manager_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query->where('organization_id', $organizationId)), Rule::notIn([$employee?->id])],
            'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
        ];
    }
}
