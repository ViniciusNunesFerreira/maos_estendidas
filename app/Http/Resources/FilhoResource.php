<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FilhoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cpf' => $this->cpf,
            'phone' => $this->phone,
            'status' => $this->status,
            'birth_date' => $this->birth_date, // Importante para o formatUtils.date
            'mother_name' => $this->mother_name,
            'photo_url' => $this->photo_url ? asset($this->photo_url) : null,
            'credit_limit' => (float) $this->credit_limit,
            'credit_used' => (float) $this->credit_used,
            'credit_available' => (float) ($this->credit_limit - $this->credit_used),
            
            // Incluindo a relação de usuário
            'user' => [
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],

            // Incluindo a relação de assinatura para preencher o card de plano
            'subscription' => $this->subscription ? [
                'plan_name' => $this->subscription->plan_name,
                'amount' => (float) $this->subscription->amount,
                'status' => $this->subscription->status,
            ] : null,
        ];
    }
}
