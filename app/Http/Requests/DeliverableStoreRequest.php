<?php

namespace App\Http\Requests;

use App\Models\OnboardingTask;
use Illuminate\Foundation\Http\FormRequest;

class DeliverableStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof OnboardingTask
            && auth()->check()
            && $task->user_id === auth()->id()
            && $task->status !== 'completed'
            && ! $task->submissions()->where('status', 'submitted')->exists();
    }

    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:2000'],
            'files' => ['required', 'array', 'min:1', 'max:8'],
            'files.*' => [
                'required',
                'file',
                'max:25600',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,rtf,csv,txt,md,json,xml,zip,png,jpg,jpeg',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Adjunta al menos un entregable.',
            'files.max' => 'Puedes adjuntar hasta 8 archivos por versión.',
            'files.*.max' => 'Cada archivo puede pesar máximo 25 MB.',
            'files.*.mimes' => 'Formato no permitido. Usa documentos de oficina, PDF, datos estructurados, ZIP o imágenes.',
        ];
    }
}
