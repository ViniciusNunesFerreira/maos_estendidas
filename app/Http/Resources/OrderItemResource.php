<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'preparation_status' => $this->preparation_status,
            'notes' => $this->notes,
            'modifiers' => $this->modifiers, // Array casted
            'product' => new ProductResource($this->whenLoaded('product')),
            'order' => new OrderResource($this->whenLoaded('order')),
        ];
    }
}