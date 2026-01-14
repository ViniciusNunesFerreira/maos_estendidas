<?php
// app/Http/Requests/LoginByCpfRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginByCpfRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cpf' => 'required|string|min:11|max:14',
            'password' => 'required|string',
            'device_info' => 'nullable|string|max:255',
        ];
    }
}