<?php

namespace App\Enums;

/**
 * Enum de Status de Pagamento
 * 
 * Define todos os status possíveis de um pagamento no sistema
 */
enum PaymentStatus: string
{
    case CREATED = 'created';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case ERROR = 'error';

    /**
     * Labels humanizados
     */
    public function label(): string
    {
        return match($this) {
            self::CREATED => 'Criado',
            self::PENDING => 'Pendente',
            self::PROCESSING => 'Processando',
            self::APPROVED => 'Aprovado',
            self::REJECTED => 'Rejeitado',
            self::CANCELLED => 'Cancelado',
            self::REFUNDED => 'Estornado',
            self::ERROR => 'Erro',
        };
    }

    /**
     * Classes CSS para badges
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::APPROVED => 'badge-success',
            self::PENDING, self::PROCESSING, self::CREATED => 'badge-warning',
            self::REJECTED, self::CANCELLED, self::ERROR => 'badge-danger',
            self::REFUNDED => 'badge-info',
        };
    }

    /**
     * Verificar se é status final (não pode mais mudar)
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::APPROVED,
            self::REJECTED,
            self::CANCELLED,
            self::REFUNDED,
        ]);
    }

    /**
     * Verificar se é status de sucesso
     */
    public function isSuccess(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Verificar se é status de falha
     */
    public function isFailure(): bool
    {
        return in_array($this, [
            self::REJECTED,
            self::CANCELLED,
            self::ERROR,
        ]);
    }

    /**
     * Verificar se está aguardando
     */
    public function isPending(): bool
    {
        return in_array($this, [
            self::CREATED,
            self::PENDING,
            self::PROCESSING,
        ]);
    }

    /**
     * Obter próximos status válidos
     */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::CREATED => [self::PENDING, self::CANCELLED],
            self::PENDING => [self::PROCESSING, self::APPROVED, self::REJECTED, self::CANCELLED],
            self::PROCESSING => [self::APPROVED, self::REJECTED, self::ERROR],
            self::APPROVED => [self::REFUNDED],
            default => [],
        };
    }

    /**
     * Verificar se pode transicionar para outro status
     */
    public function canTransitionTo(PaymentStatus $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions());
    }
}
