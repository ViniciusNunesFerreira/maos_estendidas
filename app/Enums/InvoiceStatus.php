<?php

namespace App\Enums;

/**
 * Enum de Status de Fatura (Invoice)
 * 
 * Define todos os status possíveis de uma fatura
 */
enum InvoiceStatus: string
{
    case PENDING = 'pending';       // Aguardando pagamento
    case PAID = 'paid';             // Pago
    case OVERDUE = 'overdue';       // Vencido
    case CANCELLED = 'cancelled';   // Cancelado
    case PROCESSING = 'processing'; // Processando pagamento

    /**
     * Labels humanizados
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendente',
            self::PAID => 'Pago',
            self::OVERDUE => 'Vencido',
            self::CANCELLED => 'Cancelado',
            self::PROCESSING => 'Processando',
        };
    }

    /**
     * Classes CSS para badges
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::PAID => 'badge-success',
            self::PENDING, self::PROCESSING => 'badge-warning',
            self::OVERDUE => 'badge-danger',
            self::CANCELLED => 'badge-secondary',
        };
    }

    /**
     * Cor para UI
     */
    public function color(): string
    {
        return match($this) {
            self::PAID => 'green',
            self::PENDING, self::PROCESSING => 'yellow',
            self::OVERDUE => 'red',
            self::CANCELLED => 'gray',
        };
    }

    /**
     * Verificar se pode ser paga
     */
    public function canBePaid(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::OVERDUE,
        ]);
    }

    /**
     * Verificar se é status final
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::PAID,
            self::CANCELLED,
        ]);
    }

    /**
     * Verificar se está vencida
     */
    public function isOverdue(): bool
    {
        return $this === self::OVERDUE;
    }

    /**
     * Próximos status válidos
     */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::PENDING => [self::PROCESSING, self::OVERDUE, self::CANCELLED],
            self::PROCESSING => [self::PAID, self::PENDING],
            self::OVERDUE => [self::PROCESSING, self::PAID, self::CANCELLED],
            default => [],
        };
    }

    /**
     * Verificar se pode transicionar para outro status
     */
    public function canTransitionTo(InvoiceStatus $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions());
    }
}
