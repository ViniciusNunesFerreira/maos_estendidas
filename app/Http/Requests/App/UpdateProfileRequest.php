<?php
// app/Http/Requests/App/UpdateProfileRequest.php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($userId)],
            'birth_date' => 'sometimes|required|date|before:today',
            'mother_name' => 'sometimes|required|string|max:255',
            
            // Endereço
            'street' => 'sometimes|required|string|max:255',
            'number' => 'sometimes|required|string|max:10',
            'complement' => 'nullable|string|max:100',
            'neighborhood' => 'sometimes|required|string|max:100',
            'city' => 'sometimes|required|string|max:100',
            'state' => 'sometimes|required|string|size:2',
            'zipcode' => 'sometimes|required|string|size:9',
        ];
    }
}
