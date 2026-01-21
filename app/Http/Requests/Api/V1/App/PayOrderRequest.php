<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para processar pagamento de pedido
 */
class PayOrderRequest extends FormRequest
{
    /**
     * Autorização
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Regras de validação
     */
    public function rules(): array
    {
        return [
            // Método de pagamento
            'payment_method' => [
                'required',
                Rule::in(['balance', 'pix', 'credit_card', 'debit_card', 'cash'])
            ],
            
            // Token do cartão (para credit_card)
            'card_token' => [
                Rule::requiredIf($this->payment_method === 'credit_card'),
                'nullable',
                'string',
            ],
            
            // Parcelas (para credit_card)
            'installments' => [
                Rule::requiredIf($this->payment_method === 'credit_card'),
                'nullable',
                'integer',
                'min:1',
                'max:12',
            ],
            
            // Dados do cartão (para PDV com TEF)
            'card_data' => ['nullable', 'array'],
            'card_data.pan' => ['nullable', 'string'], // Últimos 4 dígitos
            'card_data.holder_name' => ['nullable', 'string'],
            'card_data.brand' => ['nullable', 'string'],
            
            // Valor pago (para cash)
            'amount_paid' => [
                Rule::requiredIf($this->payment_method === 'cash'),
                'nullable',
                'numeric',
                'min:0',
            ],
            
            // Identificação do dispositivo (PDV)
            'device_id' => ['nullable', 'string', 'max:100'],
            
            // NSU/Comprovante (TEF)
            'nsu' => ['nullable', 'string', 'max:50'],
            'auth_code' => ['nullable', 'string', 'max:50'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Mensagens customizadas
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'Método de pagamento é obrigatório',
            'payment_method.in' => 'Método de pagamento inválido',
            
            'card_token.required' => 'Token do cartão é obrigatório',
            
            'installments.required' => 'Número de parcelas é obrigatório',
            'installments.min' => 'Mínimo de 1 parcela',
            'installments.max' => 'Máximo de 12 parcelas',
            
            'amount_paid.required' => 'Valor pago é obrigatório',
            'amount_paid.min' => 'Valor pago deve ser maior que zero',
        ];
    }

    /**
     * Preparar dados
     */
    protected function prepareForValidation(): void
    {
        // Padronizar installments
        if (!$this->has('installments')) {
            $this->merge(['installments' => 1]);
        }
    }
}