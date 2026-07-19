<?php

namespace App\Http\Requests;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LeaveStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['vacation', 'medical', 'personal', 'parental', 'other'])],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:10', 'max:1500'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $overlaps = LeaveRequest::where('user_id', auth()->id())
                ->whereIn('status', ['pending', 'approved'])
                ->whereDate('start_date', '<=', $this->date('end_date'))
                ->whereDate('end_date', '>=', $this->date('start_date'))
                ->exists();

            if ($overlaps) {
                $validator->errors()->add('start_date', 'Ya existe una solicitud pendiente o aprobada que se cruza con estas fechas.');
            }
        }];
    }
}
