<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
    return [
        'first_name' => 'required|regex:/^[a-zA-Z\s]+$/|max:255',
        'last_name' => 'required|regex:/^[a-zA-Z\s]+$/|max:255',
        'departamento_id' => 'required|exists:departamentos,id',
        'email' => 'required|unique:users,email|max:255',
        'username' => 'required|unique:users,username|max:255',
        'password' => 'required|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*?&#]/',
        'password_confirmation' => 'required|same:password',
        ];
    }   

    
}
