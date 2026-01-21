<?php

namespace App\DTOs;

use App\Exceptions\MercadoPagoException;

/**
 * DTO para processar TEF com Mercado Pago Point
 */
class ProcessPointTefDTO extends CreatePaymentIntentDTO
{
    public function __construct(
        public readonly string $orderId,
        public readonly float $amount,
        public readonly string $deviceId,
        public readonly bool $printOnTerminal = false,
        public readonly ?string $externalReference = null,
    ) {
        parent::__construct($orderId, $amount, 'point_tef');
    }

    /**
     * Criar a partir de request
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            orderId: $data['order_id'],
            amount: (float) $data['amount'],
            deviceId: $data['device_id'],
            printOnTerminal: $data['print_on_terminal'] ?? false,
            externalReference: $data['external_reference'] ?? null,
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

        if (empty($this->deviceId)) {
            throw new MercadoPagoException('Device ID da maquininha é obrigatório');
        }

        // Validar formato do device ID (ex: PAX_A910__SMARTPOS1234567890)
        if (!preg_match('/^[A-Z0-9_-]+$/i', $this->deviceId)) {
            throw new MercadoPagoException('Device ID inválido');
        }
    }

    /**
     * Obter valor em centavos (Point API exige)
     */
    public function getAmountInCents(): int
    {
        return (int) ($this->amount * 100);
    }

    /**
     * Converter para array
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'amount' => $this->amount,
            'amount_in_cents' => $this->getAmountInCents(),
            'device_id' => $this->deviceId,
            'print_on_terminal' => $this->printOnTerminal,
            'external_reference' => $this->externalReference ?? $this->orderId,
        ];
    }
}