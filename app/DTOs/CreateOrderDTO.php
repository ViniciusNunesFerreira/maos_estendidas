<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

/**
 * DTO para criação de pedidos
 * 
 * Suporta três origens:
 * - app: App mobile dos filhos
 * - pdv: PDV Desktop
 * - totem: Totem de autoatendimento
 */
class CreateOrderDTO
{
    public function __construct(
        // Identificação do Cliente
        public readonly string $customerType,  // 'filho' | 'guest'
        public readonly ?string $filhoId,      // UUID do filho (obrigatório se customerType = filho)
        
        // Dados do Visitante (obrigatório se customerType = guest)
        public readonly ?string $guestName,
        public readonly ?string $guestDocument,
        public readonly ?string $guestPhone,
        
        // Items do Pedido
        public readonly Collection $items,     // Collection de CreateOrderItemDTO
        
        // Origem e Controle
        public readonly string $origin,        // 'app' | 'pdv' | 'totem'
        public readonly ?string $deviceId,     // ID do dispositivo (PDV/Totem)
        public readonly ?string $createdByUserId,  // ID do usuário que criou (auth)
        
        // Valores
        public readonly float $subtotal,       // Calculado no backend
        public readonly float $discount,       // Desconto aplicado
        public readonly float $total,          // Total final
        
        // Observações
        public readonly ?string $notes,        // Observações gerais
        public readonly ?string $kitchenNotes, // Observações para cozinha
        
        // Metadados
        public readonly array $metadata = [],  // Dados extras (ex: mesa, local)
    ) {
        $this->validate();
    }

    /**
     * Criar DTO a partir de Request da API
     */
    public static function fromRequest(array $data, string $userId): self
    {
        \Log::info('Id usuario: '.$userId);
        // Determinar customer_type
        $customerType = $data['customer_type'] ?? 'filho';
        
        // Se tem filho_id, é sempre 'filho'
        if (!empty($data['filho_id'])) {
            $customerType = 'filho';
        }
        
        // Converter items em Collection de DTOs
        $items = collect($data['items'] ?? [])->map(fn($item) => 
            CreateOrderItemDTO::fromArray($item)
        );
        
        // Calcular valores
        $subtotal = $items->sum(fn($item) => $item->subtotal);
        $discount = (float) ($data['discount'] ?? 0);
        $total = $subtotal - $discount;
        
        return new self(
            customerType: $customerType,
            filhoId: $data['filho_id'] ?? null,
            guestName: $data['guest_name'] ?? null,
            guestDocument: $data['guest_document'] ?? null,
            guestPhone: $data['guest_phone'] ?? null,
            items: $items,
            origin: $data['origin'] ?? 'app',
            deviceId: $data['device_id'] ?? null,
            createdByUserId: $userId,
            subtotal: $subtotal,
            discount: $discount,
            total: $total,
            notes: $data['notes'] ?? null,
            kitchenNotes: $data['kitchen_notes'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Validação interna do DTO
     */
    private function validate(): void
    {
        // Validar customer_type
        if (!in_array($this->customerType, ['filho', 'guest'])) {
            throw new \InvalidArgumentException(
                "customer_type deve ser 'filho' ou 'guest'"
            );
        }
        
        // Se filho, deve ter filho_id
        if ($this->customerType === 'filho' && empty($this->filhoId)) {
            throw new \InvalidArgumentException(
                "filho_id é obrigatório quando customer_type = 'filho'"
            );
        }
        
        // Se visitante, deve ter nome
        if ($this->customerType === 'guest' && empty($this->guestName)) {
            throw new \InvalidArgumentException(
                "guest_name é obrigatório quando customer_type = 'guest'"
            );
        }
        
        // Validar origin
        if (!in_array($this->origin, ['app', 'pdv', 'totem'])) {
            throw new \InvalidArgumentException(
                "origin deve ser 'app', 'pdv' ou 'totem'"
            );
        }
        
        // Validar items
        if ($this->items->isEmpty()) {
            throw new \InvalidArgumentException(
                "Pedido deve ter pelo menos 1 item"
            );
        }
        
        // Validar valores
        if ($this->subtotal < 0 || $this->total < 0) {
            throw new \InvalidArgumentException(
                "Valores do pedido não podem ser negativos"
            );
        }
        
        if ($this->discount > $this->subtotal) {
            throw new \InvalidArgumentException(
                "Desconto não pode ser maior que o subtotal"
            );
        }
    }

    /**
     * Verificar se é pedido de filho
     */
    public function isFilho(): bool
    {
        return $this->customerType === 'filho';
    }

    /**
     * Verificar se é pedido de visitante
     */
    public function isGuest(): bool
    {
        return $this->customerType === 'guest';
    }

    /**
     * Verificar se vem do app
     */
    public function isFromApp(): bool
    {
        return $this->origin === 'app';
    }

    /**
     * Verificar se vem do PDV
     */
    public function isFromPDV(): bool
    {
        return $this->origin === 'pdv';
    }

    /**
     * Verificar se vem do Totem
     */
    public function isFromTotem(): bool
    {
        return $this->origin === 'totem';
    }

    /**
     * Converter para array (para logs/debug)
     */
    public function toArray(): array
    {
        return [
            'customer_type' => $this->customerType,
            'filho_id' => $this->filhoId,
            'guest_name' => $this->guestName,
            'guest_document' => $this->guestDocument,
            'guest_phone' => $this->guestPhone,
            'items' => $this->items->map(fn($item) => $item->toArray())->toArray(),
            'origin' => $this->origin,
            'device_id' => $this->deviceId,
            'created_by_user_id' => $this->createdByUserId,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'notes' => $this->notes,
            'kitchen_notes' => $this->kitchenNotes,
            'metadata' => $this->metadata,
        ];
    }
}