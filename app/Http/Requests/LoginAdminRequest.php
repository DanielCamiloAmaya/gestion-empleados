<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class LoginAdminRequest extends FormRequest
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
            'name' => 'required',
            'password' => 'required',
        ];
    }

    public function getCredentials()
    {
        $nameOrEmail = $this->get('name');

        if ($this->isEmail($nameOrEmail)) {
            return [
                'email' => $nameOrEmail,
                'password' => $this->get('password')
            ];
        }

        return $this->only('name', 'password');
    }

    public function isEmail($value)
    {
        $factory = $this->container->make(ValidationFactory::class);

        return !$factory->make(['name' => $value], ['name' => 'email'])->fails();
    }
}

