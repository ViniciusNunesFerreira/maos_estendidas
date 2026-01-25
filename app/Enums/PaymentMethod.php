<?php

namespace App\Enums;

/**
 * Enum de Métodos de Pagamento
 * 
 * Define todos os métodos de pagamento aceitos no sistema
 */
enum PaymentMethod: string
{
    case BALANCE = 'balance';           // Saldo interno (crédito do filho)
    case PIX = 'pix';                   // PIX (Mercado Pago)
    case CREDIT_CARD = 'credit_card';   // Cartão de Crédito
    case DEBIT_CARD = 'debit_card';     // Cartão de Débito
    case POINT_TEF = 'point_tef';       // Point TEF (maquininha)

    /**
     * Labels humanizados
     */
    public function label(): string
    {
        return match($this) {
            self::BALANCE => 'Saldo Interno',
            self::PIX => 'PIX',
            self::CREDIT_CARD => 'Cartão de Crédito',
            self::DEBIT_CARD => 'Cartão de Débito',
            self::POINT_TEF => 'Point TEF',
        };
    }

    /**
     * Ícones para UI
     */
    public function icon(): string
    {
        return match($this) {
            self::BALANCE => 'wallet',
            self::PIX => 'pix',
            self::CREDIT_CARD => 'credit-card',
            self::DEBIT_CARD => 'credit-card',
            self::POINT_TEF => 'terminal',
        };
    }

    /**
     * Verificar se requer integração externa
     */
    public function requiresExternalIntegration(): bool
    {
        return in_array($this, [
            self::PIX,
            self::CREDIT_CARD,
            self::DEBIT_CARD,
            self::POINT_TEF,
        ]);
    }

    /**
     * Verificar se é método online
     */
    public function isOnline(): bool
    {
        return in_array($this, [
            self::BALANCE,
            self::PIX,
            self::CREDIT_CARD,
            self::DEBIT_CARD,
        ]);
    }

    /**
     * Verificar se aceita parcelamento
     */
    public function allowsInstallments(): bool
    {
        return $this === self::CREDIT_CARD;
    }

    /**
     * Obter gateway de pagamento
     */
    public function gateway(): ?string
    {
        return match($this) {
            self::PIX, self::CREDIT_CARD, self::DEBIT_CARD, self::POINT_TEF => 'mercado_pago',
            default => null,
        };
    }

    /**
     * Verificar se pode ser usado em Orders
     */
    public function canBeUsedInOrders(): bool
    {
        return true; // Todos métodos podem ser usados em Orders
    }

    /**
     * Verificar se pode ser usado em Invoices
     */
    public function canBeUsedInInvoices(): bool
    {
        return match($this) {
            self::PIX, self::CREDIT_CARD, self::DEBIT_CARD => true,
            self::BALANCE, self::POINT_TEF => false, // Invoices NÃO aceitam saldo
        };
    }
}
