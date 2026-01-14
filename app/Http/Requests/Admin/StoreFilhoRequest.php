<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFilhoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'cpf' => 'required|cpf|unique:filhos,cpf',
            'birth_date' => 'required|date|before:today',
            'mother_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            
            // Endereço
            'address' => 'required|string|max:255',
            'number' => 'nullable|string|max:10',
            'complement' => 'nullable|string|max:100',
            'neighborhood' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|size:2',
            'zipcode' => 'required|string|size:9',
            
            // Configurações
            'credit_limit' => 'nullable|numeric|min:0',
            'billing_close_day' => 'nullable|integer|min:1|max:28',
            
            // Upload
            'photo' => 'nullable|image|max:2048',
        ];
    }
    
    public function messages(): array
    {
        return [
            'mother_name.required' => 'O nome da mãe é obrigatório',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'email.unique' => 'Este email já está em uso.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'As senhas não conferem.',
        ];
    }
}