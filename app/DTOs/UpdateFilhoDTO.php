<?php

namespace App\DTOs;

class UpdateFilhoDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $birthDate = null,
        public ?string $motherName = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $addressNumber = null,
        public ?string $addressComplement = null,
        public ?string $neighborhood = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $zipCode = null,
        public ?float $creditLimit = null,
        public ?int $billingCloseDay = null,
        public ?int $maxOverdueInvoices = null,
    ) {}
}