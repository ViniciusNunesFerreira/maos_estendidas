<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'description' => $this->description,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'image_url' => $this->image_url, // Utiliza o Attribute accessor definido no model
            'prices' => [
                'sale_price' => $this->price,
                'cost_price' => $this->cost_price,
            ],
            'inventory' => [
                'stock_quantity' => $this->stock_quantity,
                'min_stock_alert' => $this->min_stock_alert,
                'track_stock' => $this->track_stock,
                'is_low_stock' => $this->is_low_stock, // Atributo computado
                'stock_severity' => $this->getStockSeverity(), // Método de negócio
                'stock_color' => $this->getStockColor(),
            ],
            'settings' => [
                'type' => $this->type, // loja ou cantina
                'is_active' => $this->is_active,
                'requires_preparation' => $this->requires_preparation,
                'preparation_time_minutes' => $this->preparation_time_minutes,
                'preparation_station' => $this->preparation_station,
            ],
            'availability' => [
                'pdv' => $this->available_pdv,
                'totem' => $this->available_totem,
                'app' => $this->available_app,
            ],
            'fiscal' => [
                'ncm' => $this->ncm,
                'cest' => $this->cest,
                'icms_rate' => $this->icms_rate,
                'pis_rate' => $this->pis_rate,
                'cofins_rate' => $this->cofins_rate,
            ],
            'metadata' => [
                'tags' => $this->tags,
                'allergens' => $this->allergens,
                'calories' => $this->calories,
            ],
            // Relacionamentos carregados condicionalmente
            'category' => new CategoryResource($this->whenLoaded('category')),
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}