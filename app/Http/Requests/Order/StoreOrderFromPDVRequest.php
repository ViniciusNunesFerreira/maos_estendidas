<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validação para criação de pedidos via PDV Desktop
 */
class StoreOrderFromPDVRequest extends FormRequest
{
    /**
     * Determinar se o usuário está autorizado
     */
    public function authorize(): bool
    {
        // Apenas usuários com permissão de PDV
        return auth()->check() && auth()->user()->can('orders.create');
    }

    /**
     * Regras de validação
     */
    public function rules(): array
    {
        return [
            // Tipo de cliente
            'customer_type' => ['required', Rule::in(['filho', 'guest'])],
            
            // Dados do Filho (condicional)
            'filho_id' => [
                Rule::requiredIf($this->customer_type === 'filho'),
                'nullable',
                'uuid',
                'exists:filhos,id'
            ],
            
            // Dados do Visitante (condicional)
            'guest_name' => [
                Rule::requiredIf($this->customer_type === 'guest'),
                'nullable',
                'string',
                'max:255'
            ],
            'guest_document' => ['nullable', 'string', 'max:20'],
            'guest_phone' => ['nullable', 'string', 'max:20'],
            
            // Items do pedido
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'items.*.modifiers' => ['nullable', 'array'],
            
            // Desconto geral
            'discount' => ['nullable', 'numeric', 'min:0'],
            'discount_reason' => [
                Rule::requiredIf(fn() => $this->discount > 0),
                'nullable',
                'string',
                'max:255'
            ],
            
            // Observações
            'notes' => ['nullable', 'string', 'max:1000'],
            'kitchen_notes' => ['nullable', 'string', 'max:500'],
            
            // Identificação do dispositivo
            'device_id' => ['required', 'string', 'max:100'],
            
            // Pagamento (obrigatório para visitantes)
            'payment_method' => [
                Rule::requiredIf($this->customer_type === 'guest'),
                'nullable',
                Rule::in(['cash', 'debit', 'credit', 'pix'])
            ],
            'payment_amount' => [
                Rule::requiredIf($this->customer_type === 'guest'),
                'nullable',
                'numeric',
                'min:0'
            ],
            
            // Metadados
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Mensagens customizadas
     */
    public function messages(): array
    {
        return [
            'customer_type.required' => 'Tipo de cliente é obrigatório',
            'customer_type.in' => 'Tipo de cliente deve ser "filho" ou "visitante"',
            
            'filho_id.required' => 'Selecione um filho para continuar',
            'filho_id.exists' => 'Filho não encontrado',
            
            'guest_name.required' => 'Nome do visitante é obrigatório',
            'guest_name.max' => 'Nome do visitante não pode ter mais de 255 caracteres',
            
            'items.required' => 'Adicione pelo menos 1 item ao pedido',
            'items.min' => 'Adicione pelo menos 1 item ao pedido',
            'items.max' => 'Máximo de 50 itens por pedido',
            
            'items.*.product_id.required' => 'ID do produto é obrigatório',
            'items.*.product_id.exists' => 'Produto não encontrado',
            
            'items.*.quantity.required' => 'Quantidade é obrigatória',
            'items.*.quantity.min' => 'Quantidade mínima é 1',
            'items.*.quantity.max' => 'Quantidade máxima é 99',
            
            'discount_reason.required' => 'Motivo do desconto é obrigatório',
            
            'device_id.required' => 'Identificação do PDV é obrigatória',
            
            'payment_method.required' => 'Forma de pagamento é obrigatória para visitantes',
            'payment_amount.required' => 'Valor do pagamento é obrigatório',
        ];
    }

    /**
     * Preparar dados para validação
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'origin' => 'pdv',
            'created_by_user_id' => auth()->id(),
        ]);
    }

    /**
     * Validação adicional
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Se for filho, validar status
            if ($this->customer_type === 'filho' && $this->filho_id) {
                $filho = \App\Models\Filho::find($this->filho_id);
                
                if ($filho && $filho->status !== 'active') {
                    $validator->errors()->add(
                        'filho_status',
                        'Este filho está com cadastro inativo'
                    );
                }
                
                if ($filho && !$filho->can_purchase) {
                    $validator->errors()->add(
                        'filho_blocked',
                        'Este filho está bloqueado por inadimplência'
                    );
                }
            }
            
            // Se for visitante, validar pagamento
            if ($this->customer_type === 'guest') {
                $total = collect($this->items)->sum(function ($item) {
                    $qty = $item['quantity'] ?? 0;
                    $price = $item['unit_price'] ?? 0;
                    $disc = $item['discount'] ?? 0;
                    return ($qty * $price) - $disc;
                });
                
                $total -= ($this->discount ?? 0);
                
                if ($this->payment_amount < $total) {
                    $validator->errors()->add(
                        'payment_amount',
                        'Valor do pagamento é menor que o total do pedido'
                    );
                }
            }
        });
    }
}