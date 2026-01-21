<?php

namespace App\DTOs;

use App\Exceptions\MercadoPagoException;

/**
 * DTO para processar Checkout Transparente (PIX ou Cartão)
 */
class ProcessCheckoutDTO extends CreatePaymentIntentDTO
{
    public function __construct(
        public readonly string $orderId,
        public readonly float $amount,
        public readonly string $paymentMethod, // 'pix' | 'credit_card' | 'debit_card'
        public readonly ?string $cardToken = null,
        public readonly int $installments = 1,
        public readonly ?array $payer = null,
    ) {
        parent::__construct($orderId, $amount, $paymentMethod);
    }

    /**
     * Criar a partir de request
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            orderId: $data['order_id'],
            amount: (float) $data['amount'],
            paymentMethod: $data['payment_method'],
            cardToken: $data['card_token'] ?? null,
            installments: $data['installments'] ?? 1,
            payer: $data['payer'] ?? null,
        );
    }

    /**
     * Validar DTO
     */
    public function validate(): void
    {
        if (empty($this->orderId)) {
            throw new MercadoPagoException('Order ID é obrigatório');
        }

        if ($this->amount <= 0) {
            throw new MercadoPagoException('Valor deve ser maior que zero');
        }

        if (!in_array($this->paymentMethod, ['pix', 'credit_card', 'debit_card'])) {
            throw new MercadoPagoException('Método de pagamento inválido');
        }

        // Validações específicas de cartão
        if (in_array($this->paymentMethod, ['credit_card', 'debit_card'])) {
            if (empty($this->cardToken)) {
                throw new MercadoPagoException('Token do cartão é obrigatório');
            }

            if ($this->installments < 1 || $this->installments > 12) {
                throw new MercadoPagoException('Número de parcelas inválido (1-12)');
            }
        }
    }

    /**
     * Verificar se é PIX
     */
    public function isPix(): bool
    {
        return $this->paymentMethod === 'pix';
    }

    /**
     * Verificar se é cartão
     */
    public function isCard(): bool
    {
        return in_array($this->paymentMethod, ['credit_card', 'debit_card']);
    }

    /**
     * Converter para array
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'amount' => $this->amount,
            'payment_method' => $this->paymentMethod,
            'card_token' => $this->cardToken,
            'installments' => $this->installments,
            'payer' => $this->payer,
        ];
    }
}