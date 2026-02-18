<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            
            // Cliente
            'customer' => [
                'type' => $this->customer_type,
                'name' => $this->customer_name,
                'cpf' => $this->customer_cpf,
                'phone' => $this->customer_phone,
            ],
            
            // Filho (se aplicável)
            'filho' => $this->when($this->filho, function() {
                return [
                    'id' => $this->filho->id,
                    'name' => $this->filho->user->name ?? null,
                    'cpf' => $this->filho->cpf_formatted,
                    'credit_available' => $this->filho->credit_available,
                ];
            }),
            
            // Valores
            'pricing' => [
                'subtotal' => (float) $this->subtotal,
                'discount' => (float) $this->discount_amount,
                'total' => (float) $this->total,
            ],

            'total' => (float) $this->total,
            
            // Status
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            
            // Items
            'items' => $this->when($this->relationLoaded('items'), function() {
                return $this->items->map(fn($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                    'total' => (float) $item->total,
                    'preparation_status' => $item->requires_preparation ? $item->preparation_status : 'delivered',
                    'product' => $this->when($item->relationLoaded('product'), function() use ($item) {
                        return [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'image_url' => $item->product->image_url,
                        ];
                    }),
                ]);
            }),
            
            // Pagamento
            'payment' => $this->when($this->relationLoaded('payment'), function() {
                return $this->payment ? [
                    'id' => $this->payment->id,
                    'method' => $this->payment->method,
                    'amount' => (float) $this->payment->amount,
                    'status' => $this->payment->status,
                    'paid_at' => $this->payment->confirmed_at?->toIso8601String(),
                ] : null;
            }),
            
            // Payment Intent
            'payment_intent' => $this->when($this->payment_intent_id, function() {
                return [
                    'id' => $this->payment_intent_id,
                    // Dados adicionais serão carregados via endpoint específico
                ];
            }),
            
            // Origem
            'origin' => $this->origin,
            
            // Timestamps
            'timestamps' => [
                'created_at' => $this->created_at->toIso8601String(),
                'paid_at' => $this->paid_at?->toIso8601String(),
                'preparing_at' => $this->preparing_at?->toIso8601String(),
                'ready_at' => $this->ready_at?->toIso8601String(),
                'delivered_at' => $this->delivered_at?->toIso8601String(),
                'completed_at' => $this->completed_at?->toIso8601String(),
                'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            ],
            
            // Observações
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
        ];
    }
}