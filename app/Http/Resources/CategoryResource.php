<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transforma o recurso em um array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id, // Utiliza o Trait HasUuid presente no model
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'description' => $this->description,
            'appearance' => [
                'icon' => $this->icon,
                'color' => $this->color,
            ],
            'sorting' => [
                'order' => $this->order,
                'is_active' => $this->is_active,
            ],
            // Relacionamentos Hierárquicos
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            
            // Relacionamento com Produtos
            'products' => ProductResource::collection($this->whenLoaded('products')),
            
            // Metadados de contagem (opcional, útil para menus)
            'products_count' => $this->whenCounted('products'),
            'children_count' => $this->whenCounted('children'),
            
            'timestamps' => [
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
        ];
    }
}