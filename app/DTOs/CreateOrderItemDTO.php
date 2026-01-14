<?php

namespace App\DTOs;

/**
 * DTO para items individuais do pedido
 */
class CreateOrderItemDTO
{
    public function __construct(
        public readonly string $productId,     // UUID do produto
        public readonly int $quantity,         // Quantidade
        public readonly float $unitPrice,      // Preço unitário (snapshot)
        public readonly float $subtotal,       // quantity * unitPrice
        public readonly float $discount,       // Desconto no item
        public readonly float $total,          // subtotal - discount
        public readonly ?string $notes,        // Observações do item
        public readonly ?array $modifiers,     // Modificadores/complementos
        
        // Metadados (preenchidos pelo backend)
        public readonly ?string $productName = null,
        public readonly ?string $productSku = null,
    ) {
        $this->validate();
    }

    /**
     * Criar DTO a partir de array
     */
    public static function fromArray(array $data): self
    {
        $quantity = (int) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        
        $subtotal = $quantity * $unitPrice;
        $total = $subtotal - $discount;
        
        return new self(
            productId: $data['product_id'],
            quantity: $quantity,
            unitPrice: $unitPrice,
            subtotal: $subtotal,
            discount: $discount,
            total: $total,
            notes: $data['notes'] ?? null,
            modifiers: $data['modifiers'] ?? null,
            productName: $data['product_name'] ?? null,
            productSku: $data['product_sku'] ?? null,
        );
    }

    /**
     * Validação interna
     */
    private function validate(): void
    {
        if (empty($this->productId)) {
            throw new \InvalidArgumentException(
                "product_id é obrigatório"
            );
        }
        
        if ($this->quantity < 1) {
            throw new \InvalidArgumentException(
                "quantity deve ser maior que 0"
            );
        }
        
        if ($this->quantity > 99) {
            throw new \InvalidArgumentException(
                "quantity não pode ser maior que 99"
            );
        }
        
        if ($this->unitPrice < 0) {
            throw new \InvalidArgumentException(
                "unit_price não pode ser negativo"
            );
        }
        
        if ($this->discount > $this->subtotal) {
            throw new \InvalidArgumentException(
                "discount não pode ser maior que o subtotal do item"
            );
        }
    }

    /**
     * Converter para array (para salvar no banco)
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'notes' => $this->notes,
            'modifiers' => $this->modifiers,
            'product_name' => $this->productName,
            'product_sku' => $this->productSku,
        ];
    }
}