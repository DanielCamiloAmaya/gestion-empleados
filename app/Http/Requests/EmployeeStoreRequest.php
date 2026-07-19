<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EmployeeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        $organizationId = auth('admin')->user()->organization_id;

        return [
            'employee_code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('users')->where(fn ($query) => $query->where('organization_id', $organizationId))],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')->where(fn ($query) => $query->where('organization_id', $organizationId))],
            'username' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users')->where(fn ($query) => $query->where('organization_id', $organizationId))],
            'departamento_id' => ['required', Rule::exists('departamentos', 'id')->where(fn ($query) => $query->where('organization_id', $organizationId))],
            'job_title' => ['required', 'string', 'max:150'],
            'employment_status' => ['required', Rule::in(['onboarding', 'active', 'leave', 'inactive'])],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contractor', 'intern'])],
            'hire_date' => ['required', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:150'],
            'manager_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query->where('organization_id', $organizationId))],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
        ];
    }
}
