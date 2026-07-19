<?php

namespace App\Http\Requests;

use App\Models\OnboardingSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliverableReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        if (! $submission instanceof OnboardingSubmission || $submission->status !== 'submitted') {
            return false;
        }

        if (auth('admin')->check()) {
            return true;
        }

        return auth()->check()
            && $submission->task()->whereHas('employee', fn ($query) => $query->where('manager_id', auth()->id()))->exists();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['nullable', 'required_if:status,rejected', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['review_note.required_if' => 'Explica el motivo del rechazo para que el empleado pueda corregir la entrega.'];
    }
}
