<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'A senha atual é obrigatória',
            'password.required' => 'A nova senha é obrigatória',
            'password.min' => 'A nova senha deve ter no mínimo 8 caracteres',
            'password.confirmed' => 'As senhas não conferem',
        ];
    }
}
