<?php

namespace App\DTOs;

/**
 * DTO base para criação de Payment Intent
 */
abstract class CreatePaymentIntentDTO
{
    public function __construct(
        public readonly string $orderId,
        public readonly float $amount,
        public readonly string $paymentMethod,
    ) {}

    /**
     * Validar DTO
     */
    abstract public function validate(): void;

    /**
     * Converter para array
     */
    abstract public function toArray(): array;
}