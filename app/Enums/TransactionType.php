<?php

namespace App\Enums;

/**
 * Enum de Tipos de Transação
 * 
 * Define todos os tipos de transações de crédito possíveis
 */
enum TransactionType: string
{
    case DEBIT = 'debit';                           // Débito (consumo de crédito)
    case CREDIT = 'credit';                         // Crédito (adição manual)
    case MENSALIDADE_CREDIT = 'mensalidade_credit'; // Restauração de crédito (pagamento fatura)
    case LIMIT_CHANGE = 'limit_change';             // Alteração de limite

    /**
     * Labels humanizados
     */
    public function label(): string
    {
        return match($this) {
            self::DEBIT => 'Débito (Compra)',
            self::CREDIT => 'Crédito (Ajuste)',
            self::MENSALIDADE_CREDIT => 'Restauração (Pagamento Fatura)',
            self::LIMIT_CHANGE => 'Alteração de Limite',
        };
    }

    /**
     * Descrição detalhada
     */
    public function description(): string
    {
        return match($this) {
            self::DEBIT => 'Consumo de crédito em compra',
            self::CREDIT => 'Adição manual de crédito',
            self::MENSALIDADE_CREDIT => 'Crédito restaurado após pagamento de fatura',
            self::LIMIT_CHANGE => 'Alteração no limite de crédito',
        };
    }

    /**
     * Ícone para UI
     */
    public function icon(): string
    {
        return match($this) {
            self::DEBIT => 'minus-circle',
            self::CREDIT => 'plus-circle',
            self::MENSALIDADE_CREDIT => 'refresh-cw',
            self::LIMIT_CHANGE => 'settings',
        };
    }

    /**
     * Cor para UI
     */
    public function color(): string
    {
        return match($this) {
            self::DEBIT => 'red',
            self::CREDIT, self::MENSALIDADE_CREDIT => 'green',
            self::LIMIT_CHANGE => 'blue',
        };
    }

    /**
     * Verificar se reduz saldo disponível
     */
    public function reducesBalance(): bool
    {
        return $this === self::DEBIT;
    }

    /**
     * Verificar se aumenta saldo disponível
     */
    public function increasesBalance(): bool
    {
        return in_array($this, [
            self::CREDIT,
            self::MENSALIDADE_CREDIT,
        ]);
    }

    /**
     * Multiplicador para cálculo de saldo
     * (+1 para crédito, -1 para débito)
     */
    public function multiplier(): int
    {
        return match($this) {
            self::DEBIT => -1,
            self::CREDIT, self::MENSALIDADE_CREDIT => 1,
            self::LIMIT_CHANGE => 0, // Não afeta saldo, apenas limite
        };
    }

    /**
     * Verificar se requer aprovação
     */
    public function requiresApproval(): bool
    {
        return in_array($this, [
            self::CREDIT,
            self::LIMIT_CHANGE,
        ]);
    }
}
