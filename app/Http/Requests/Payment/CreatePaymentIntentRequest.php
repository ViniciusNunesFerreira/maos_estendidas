<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para criar Payment Intent no PDV
 * Suporta: PIX, Crédito Manual, Débito Manual
 */
class CreatePaymentIntentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
       // return $this->user()->can('pdv.create-payments');
       return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Identificação do Pedido
            'order_id' => [
                'required',
                'uuid',
                'exists:orders,id',
            ],

            // Método de Pagamento
            'payment_method' => [
                'required',
                'string',
                Rule::in(['pix', 'credit_card', 'debit_card']),
            ],

            // Valor
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
            ],

            // Device ID do PDV
            'device_id' => [
                'required',
                'string',
                'max:100',
            ],

            // Operador
            'operator_id' => [
                'nullable',
                'uuid',
                'exists:users,id',
            ],

            // Dados específicos do PIX (opcionais, serão preenchidos pelo service)
            'pix_expiration_minutes' => [
                'nullable',
                'integer',
                'min:5',
                'max:1440', // 24 horas
            ],

            // Dados específicos de Cartão Manual (crédito/débito)
            'card_last_digits' => [
                'nullable',
                'string',
                'size:4',
                'regex:/^\d{4}$/',
            ],

            'card_brand' => [
                'nullable',
                'string',
                Rule::in(['visa', 'mastercard', 'elo', 'amex', 'hipercard', 'other']),
            ],

            'installments' => [
                'nullable',
                'integer',
                'min:1',
                'max:12',
            ],

            // ID da transação na maquininha POS (para crédito/débito manual)
            'pos_transaction_id' => [
                'nullable',
                'string',
                'max:100',
            ],

            'pos_authorization_code' => [
                'nullable',
                'string',
                'max:50',
            ],

            // Metadados
            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'O pedido é obrigatório.',
            'order_id.exists' => 'Pedido não encontrado.',
            'payment_method.required' => 'Método de pagamento é obrigatório.',
            'payment_method.in' => 'Método de pagamento inválido. Use: pix, credit_card ou debit_card.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.min' => 'O valor mínimo é R$ 0,01.',
            'amount.max' => 'O valor máximo é R$ 999.999,99.',
            'card_last_digits.size' => 'Últimos 4 dígitos do cartão devem ter exatamente 4 caracteres.',
            'card_last_digits.regex' => 'Últimos 4 dígitos do cartão devem ser numéricos.',
            'installments.min' => 'Número mínimo de parcelas é 1.',
            'installments.max' => 'Número máximo de parcelas é 12.',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Normalizar payment_method para lowercase
        if ($this->has('payment_method')) {
            $this->merge([
                'payment_method' => strtolower($this->input('payment_method')),
            ]);
        }

        // Se não forneceu installments, assume 1
        if ($this->input('payment_method') === 'credit_card' && !$this->has('installments')) {
            $this->merge(['installments' => 1]);
        }

        // Se não forneceu pix_expiration_minutes, assume 30 minutos
        if ($this->input('payment_method') === 'pix' && !$this->has('pix_expiration_minutes')) {
            $this->merge(['pix_expiration_minutes' => 30]);
        }
    }

    /**
     * Verificar se é PIX
     */
    public function isPix(): bool
    {
        return $this->input('payment_method') === 'pix';
    }

    /**
     * Verificar se é cartão (crédito ou débito)
     */
    public function isCard(): bool
    {
        return in_array($this->input('payment_method'), ['credit_card', 'debit_card']);
    }

    /**
     * Verificar se é crédito
     */
    public function isCreditCard(): bool
    {
        return $this->input('payment_method') === 'credit_card';
    }

    /**
     * Verificar se é débito
     */
    public function isDebitCard(): bool
    {
        return $this->input('payment_method') === 'debit_card';
    }
}