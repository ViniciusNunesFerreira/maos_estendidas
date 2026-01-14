<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validação para criação de pedidos via App dos Filhos
 */
class StoreOrderFromAppRequest extends FormRequest
{
    /**
     * Determinar se o usuário está autorizado
     */
    public function authorize(): bool
    {
       
        // Usuário deve ter um filho associado
        return auth()->check() && auth()->user()->filho !== null;
    }

    /**
     * Regras de validação
     */
    public function rules(): array
    {
       
        return [
            // Items do pedido
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'items.*.modifiers' => ['nullable', 'array'],
            
            // Observações
            'notes' => ['nullable', 'string', 'max:1000'],
            'kitchen_notes' => ['nullable', 'string', 'max:500'],
            
            // Metadados opcionais
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Mensagens de erro customizadas
     */
    public function messages(): array
    {
        return [
            'items.required' => 'O pedido deve conter pelo menos 1 item',
            'items.min' => 'O pedido deve conter pelo menos 1 item',
            'items.max' => 'O pedido não pode ter mais de 50 itens',
            
            'items.*.product_id.required' => 'ID do produto é obrigatório',
            'items.*.product_id.uuid' => 'ID do produto inválido',
            'items.*.product_id.exists' => 'Produto não encontrado',
            
            'items.*.quantity.required' => 'Quantidade é obrigatória',
            'items.*.quantity.integer' => 'Quantidade deve ser um número inteiro',
            'items.*.quantity.min' => 'Quantidade mínima é 1',
            'items.*.quantity.max' => 'Quantidade máxima é 99 por item',
            
            'items.*.notes.max' => 'Observações do item não podem ter mais de 500 caracteres',
            
            'notes.max' => 'Observações não podem ter mais de 1000 caracteres',
            'kitchen_notes.max' => 'Observações da cozinha não podem ter mais de 500 caracteres',
        ];
    }

    /**
     * Preparar dados para validação
     */
    protected function prepareForValidation(): void
    {
        // Adicionar origin = 'app' automaticamente
        $this->merge([
            'origin' => 'app',
            'filho_id' => auth()->user()->filho->id,
            'customer_type' => 'filho',
        ]);
    }

    /**
     * Validação adicional após regras básicas
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar se filho está ativo
            $filho = auth()->user()->filho;
            
            if ($filho->status !== 'active') {
                $validator->errors()->add(
                    'filho_status',
                    'Seu cadastro está inativo. Entre em contato com a administração.'
                );
            }

            
            // Validar se filho não está bloqueado
            if (!$filho->can_purchase) {
                $reasons = [];
                
                if ($filho->is_blocked_by_debt) {
                    $reasons[] = "Você possui {$filho->total_overdue_invoices} fatura(s) vencida(s)";
                }
                
                if ($filho->total_overdue_invoices >= $filho->max_overdue_invoices) {
                    $reasons[] = "Limite de faturas vencidas atingido ({$filho->max_overdue_invoices})";
                }
                
                $validator->errors()->add(
                    'filho_blocked',
                    implode('. ', $reasons) . '. Regularize para continuar comprando.'
                );
            }
        });
    }
}