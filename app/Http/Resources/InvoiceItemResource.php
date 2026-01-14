<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'category' => $this->category,
            'location' => $this->location, // Ex: 'loja' ou 'cantina'
            'purchase_date' => $this->purchase_date,
            'pricing' => [
                'quantity' => $this->quantity,
                'unit_price' => $this->unit_price,
                'subtotal' => $this->subtotal,
                'discount_amount' => $this->discount_amount,
                'total' => $this->total,
            ],
            'flags' => [
                'is_from_loja' => $this->isFromLoja(),
                'is_from_cantina' => $this->isFromCantina(),
            ],
            // Relacionamentos
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'order' => new OrderResource($this->whenLoaded('order')),
            'order_item' => new OrderItemResource($this->whenLoaded('orderItem')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}