<?php
// app/Http/Requests/Admin/CreateFilhoRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateFilhoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:6',
            'cpf' => 'required|string|size:11|unique:filhos,cpf',
            'birth_date' => 'required|date|before:today',
            'mother_name' => 'required|string|max:255', // NOVO - OBRIGATÓRIO
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'address_number' => 'nullable|string|max:20',
            'address_complement' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|size:2',
            'zip_code' => 'required|string|size:8',
        ];
    }

    public function messages(): array
    {
        return [
            'mother_name.required' => 'O nome da mãe é obrigatório',
            'cpf.unique' => 'Este CPF já está cadastrado',
            'cpf.size' => 'CPF deve ter 11 dígitos',
        ];
    }
}