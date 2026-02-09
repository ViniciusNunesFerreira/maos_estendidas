<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request Unificado para criação de pedidos
 * Detecta origem automaticamente (App ou PDV)
 */
class StoreOrderRequest extends FormRequest
{
    /**
     * Determinar se o usuário está autorizado
     */
    public function authorize(): bool
    {
        // App: usuário deve ter filho
        if ($this->isFromApp()) {
            return auth()->check() && auth()->user()->filho !== null;
        }
        
        // PDV: usuário deve ter permissão
        if ($this->isFromPDV()) {
            \Log::info('veio do pdv');
            return auth()->check() && auth()->user()->can('orders.create');
        }
        
        return false;
    }

    /**
     * Regras de validação
     */
    public function rules(): array
    {
        $rules = [
            // Items do pedido (comum para App e PDV)
            'origin' => ['required'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.subtotal' => ['required'],
            'created_by_user_id' => 'nullable|uuid',
            'subtotal' => ['required'],
            'total' => ['required']
            
            // Observações gerais
           // 'notes' => ['nullable', 'string', 'max:1000'],
           // 'kitchen_notes' => ['nullable', 'string', 'max:500'],
            
           
        ];

        // Regras específicas do PDV
        if ($this->isFromPDV()) {
            $rules = array_merge($rules, [
                // Tipo de cliente (PDV permite filho ou visitante)
                'customer_type' => ['required', Rule::in(['filho', 'guest'])],
                
                // Filho (condicional)
                'filho_id' => [
                    Rule::requiredIf($this->customer_type === 'filho'),
                    'nullable',
                    'uuid',
                    'exists:filhos,id'
                ],
                
                // Visitante (condicional)
                'guest_name' => [
                    Rule::requiredIf($this->customer_type === 'guest'),
                    'nullable',
                    'string',
                    'max:255'
                ],
                'guest_document' => ['nullable', 'string', 'max:20'],
                'guest_phone' => ['nullable', 'string', 'max:20'],
                
                // Preços e descontos (PDV pode customizar)
                'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
                'items.*.discount' => ['nullable', 'numeric', 'min:0'],
                'discount' => ['nullable', 'numeric', 'min:0'],
                'discount_reason' => [
                    Rule::requiredIf(fn() => ($this->discount ?? 0) > 0),
                    'nullable',
                    'string',
                    'max:255'
                ],
                
                // Device ID (PDV)
                'device_id' => ['nullable', 'string', 'max:100'],

                'payment_method_chosen' => 'required|string',
                
                // Pagamento imediato para visitantes
               /* 'payment_method_chosen' => [
                    Rule::requiredIf($this->customer_type === 'guest'),
                    'nullable',
                    Rule::in(['cash', 'debit', 'credit', 'pix'])
                ],
                'payment_method_chosen' => [
                    Rule::requiredIf($this->customer_type === 'guest'),
                    'nullable',
                    'numeric',
                    'min:0'
                ],*/
            ]);
        }

        return $rules;
    }

    /**
     * Mensagens customizadas
     */
    public function messages(): array
    {
        return [
            // Items
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
            
            // PDV específico
            'customer_type.required' => 'Tipo de cliente é obrigatório',
            'customer_type.in' => 'Tipo de cliente deve ser "filho" ou "guest"',
            
            'filho_id.required' => 'Selecione um filho',
            'filho_id.exists' => 'Filho não encontrado',
            
            'guest_name.required' => 'Nome do visitante é obrigatório',
            
            'device_id.required' => 'Identificação do PDV é obrigatória',

            'discount_reason.required' => 'Motivo do desconto é obrigatório',
            
           /* 'payment_method.required' => 'Forma de pagamento é obrigatória para visitantes',
            'payment_amount.required' => 'Valor do pagamento é obrigatório',*/
            
            
        ];
    }

    /**
     * Preparar dados antes da validação
     */
    protected function prepareForValidation(): void
    {
        $data = [
            'origin' => $this->detectOrigin(),
        ];

        // App: adicionar filho_id automaticamente
        if ($this->isFromApp()) {
            $data['filho_id'] = auth()->user()->filho->id;
            $data['customer_type'] = 'filho';
        }

        // PDV: adicionar created_by
        if ($this->isFromPDV()) {
            $data['created_by_user_id'] = auth()->id();
        }

        $this->merge($data);
    }

    /**
     * Validação adicional
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            
            // Validação de Filho (App ou PDV com filho)
            if ($this->customer_type === 'filho' && $this->filho_id) {
                $filho = \App\Models\Filho::find($this->filho_id);
                
                if (!$filho) {
                    $validator->errors()->add('filho_id', 'Filho não encontrado');
                    return;
                }
                
                // Status inativo
                if ($filho->status !== 'active') {
                    $validator->errors()->add(
                        'filho_status',
                        $this->isFromApp() 
                            ? 'Seu cadastro está inativo. Entre em contato com a administração.'
                            : 'Este filho está com cadastro inativo'
                    );
                }
                
                // Bloqueado por inadimplência
                if ($filho->is_blocked_by_debt) {
                    $reasons = [];

                    $total_overdue_invoices = $filho->invoices()->where('status', 'overdue')->count();
                    
                    if ($filho->is_blocked_by_debt) {
                        $reasons[] = "Possui {$total_overdue_invoices} fatura(s) vencida(s)";
                    }

                    if ($total_overdue_invoices >= $filho->max_overdue_invoices) {
                        $reasons[] = "Limite de faturas vencidas atingido";
                    }
                    
                    $message = implode('. ', $reasons);
                    
                    $validator->errors()->add(
                        'filho_blocked',
                        $this->isFromApp()
                            ? $message . '. Regularize para continuar comprando.'
                            : 'Este filho está bloqueado por inadimplência'
                    );
                }
            }
            
            // Validação de Visitante (apenas PDV)
            if ($this->isFromPDV() && $this->customer_type === 'guest') {
                // Calcular total do pedido
                $total = collect($this->items)->sum(function ($item) {
                    $qty = $item['quantity'] ?? 0;
                    $price = $item['unit_price'] ?? 0;
                    $disc = $item['discount'] ?? 0;
                    return ($qty * $price) - $disc;
                });
                
                $total -= ($this->discount ?? 0);
                
                // Validar pagamento
                if (($this->payment_amount ?? 0) < $total) {
                    $validator->errors()->add(
                        'payment_amount',
                        'Valor do pagamento é menor que o total do pedido'
                    );
                }
            }
        });
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Detectar origem da requisição
     */
    private function detectOrigin(): string
    {
        // Verificar header customizado
        $origin = $this->header('X-Origin');
        if ($origin && in_array($origin, ['app', 'pdv'])) {
            return $origin;
        }

        // Verificar User-Agent
        $userAgent = $this->header('User-Agent', '');
        
        if (str_contains($userAgent, 'PDV-Desktop')) {
            return 'pdv';
        }
        
        if (str_contains($userAgent, 'MaosEstendidas-App')) {
            return 'app';
        }

        // Verificar device_id (PDV sempre envia)
        if ($this->has('device_id')) {
            return 'pdv';
        }

        // Padrão: App
        return 'app';
    }

    /**
     * Verificar se é do App
     */
    private function isFromApp(): bool
    {
        return $this->detectOrigin() === 'app';
    }

    /**
     * Verificar se é do PDV
     */
    private function isFromPDV(): bool
    {
        return $this->detectOrigin() === 'pdv';
    }
}