<?php

namespace App\DTOs;

use App\Http\Requests\Admin\StoreFilhoRequest;

class CreateFilhoDTO
{
    public function __construct(
        public string $name,
        public string $cpf,
        public string $birthDate,
        public string $motherName, // NOVO
        public string $phone,
        public string $password,
        public ?string $email = null,
        public ?string $address = null,
        public ?string $addressNumber = null,
        public ?string $addressComplement = null,
        public ?string $neighborhood = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $zipCode = null,
        public ?float $creditLimit = 1000.00,
        public ?int $billingCloseDay = 28
    ) {}


    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            cpf: $data['cpf'],
            birthDate: $data['birth_date'],
            motherName: $data['mother_name'],
            phone: $data['phone'],
            password: $data['password'] ?? '12345678',
            email: $data['email'] ?? null,
            address: $data['address'] ?? null,
            addressNumber: $data['number'] ?? null, 
            addressComplement: $data['complement'] ?? null,
            neighborhood: $data['neighborhood'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            zipCode: $data['zipcode'] ?? null,
            creditLimit: isset($data['credit_limit']) ? (float) $data['credit_limit'] : 1000.00,
            billingCloseDay: isset($data['billing_close_day']) ? (int) $data['billing_close_day'] : 28
        );
    }

    public static function fromRequest(StoreFilhoRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            cpf: $request->validated('cpf'),
            birthDate: $request->validated('birth_date'),
            motherName: $request->validated('mother_name'),
            phone: $request->validated('phone'),
            password: $request->validated('password') ?? '12345678',
            address: $request->validated('address') ?? null,
            addressNumber: $request->validated('number') ?? null, 
            addressComplement: $request->validated('complement') ?? null,
            neighborhood: $request->validated('neighborhood') ?? null,
            city: $request->validated('city') ?? null,
            state: $request->validated('state') ?? null,
            zipCode: $request->validated('zipcode') ?? null,
            creditLimit: 1000.00,
            billingCloseDay: $request->validated('billing_close_day') ?? 28
        );
    }
}