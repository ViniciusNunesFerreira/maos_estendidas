<?php

namespace App\Enums;

/**
 * Enum de Status de Pedido (Order)
 * 
 * Define todos os status possíveis de um pedido
 */
enum OrderStatus: string
{
    case PENDING = 'pending';             // Aguardando pagamento
    case PROCESSING = 'processing';       // Pagamento em processamento
    case CONFIRMED = 'confirmed';         // Pagamento confirmado
    case PREPARING = 'preparing';         // Em preparação
    case READY = 'ready';                 // Pronto para retirada
    case COMPLETED = 'completed';         // Entregue/Completo
    case CANCELLED = 'cancelled';         // Cancelado

    /**
     * Labels humanizados
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Aguardando Pagamento',
            self::PROCESSING => 'Processando Pagamento',
            self::CONFIRMED => 'Pagamento Confirmado',
            self::PREPARING => 'Em Preparação',
            self::READY => 'Pronto para Retirada',
            self::COMPLETED => 'Completo',
            self::CANCELLED => 'Cancelado',
        };
    }

    /**
     * Classes CSS para badges
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::COMPLETED => 'badge-success',
            self::CONFIRMED, self::PREPARING, self::READY => 'badge-info',
            self::PENDING, self::PROCESSING => 'badge-warning',
            self::CANCELLED => 'badge-danger',
        };
    }

    /**
     * Cor para UI
     */
    public function color(): string
    {
        return match($this) {
            self::COMPLETED => 'green',
            self::CONFIRMED, self::PREPARING, self::READY => 'blue',
            self::PENDING, self::PROCESSING => 'yellow',
            self::CANCELLED => 'red',
        };
    }

    /**
     * Verificar se é status final
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
        ]);
    }

    /**
     * Verificar se pedido está ativo
     */
    public function isActive(): bool
    {
        return !$this->isFinal();
    }

    /**
     * Verificar se pode ser cancelado
     */
    public function isCancellable(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::PROCESSING,
        ]);
    }

    /**
     * Próximos status válidos
     */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::PENDING => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::PREPARING, self::CANCELLED],
            self::PREPARING => [self::READY],
            self::READY => [self::COMPLETED],
            default => [],
        };
    }

    /**
     * Verificar se pode transicionar para outro status
     */
    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions());
    }

    /**
     * Obter próximo status automático
     */
    public function nextAutoStatus(): ?OrderStatus
    {
        return match($this) {
            self::PENDING => null, // Aguarda pagamento manual
            self::PROCESSING => null, // Aguarda confirmação de pagamento
            self::CONFIRMED => self::PREPARING, // Auto-transição
            self::PREPARING => self::READY, // Requer confirmação manual
            self::READY => self::COMPLETED, // Requer confirmação manual
            default => null,
        };
    }
}
