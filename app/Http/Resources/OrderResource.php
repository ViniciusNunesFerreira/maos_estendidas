<?php
// app/Http/Resources/OrderResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'origin' => $this->origin,
            'status' => $this->status,
            
            // Valores
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            
            // Relacionamentos
            'filho' => $this->whenLoaded('filho', fn() => new FilhoResource($this->filho)),
            'items' => $this->whenLoaded('items', fn() => OrderItemResource::collection($this->items)),
            'payment' => $this->whenLoaded('payment', fn() => new PaymentResource($this->payment)),
            
            // Timestamps
            'paid_at' => $this->paid_at,
            'preparing_at' => $this->preparing_at,
            'ready_at' => $this->ready_at,
            'delivered_at' => $this->delivered_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}