<?php
// app/Http/Requests/Admin/UpdateFilhoRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFilhoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        $filhoId = $this->route('filho')->id;
        
        return [
            'name' => 'required|string|max:255',
            'cpf' => ['required', 'cpf', Rule::unique('filhos')->ignore($filhoId)],
            'birth_date' => 'required|date|before:today',
            'mother_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => ['nullable', 'email', Rule::unique('users')->ignore($this->route('filho')->user_id)],
            
            // Endereço
            'street' => 'required|string|max:255',
            'number' => 'required|string|max:10',
            'complement' => 'nullable|string|max:100',
            'neighborhood' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|size:2',
            'zipcode' => 'required|string|size:9',
            
            // Configurações
            'credit_limit' => 'nullable|numeric|min:0',
            'billing_close_day' => 'nullable|integer|min:1|max:28',
            'status' => 'required|in:pending,active,blocked,inactive',
            
            // Upload
            'photo' => 'nullable|image|max:2048',
        ];
    }
}
