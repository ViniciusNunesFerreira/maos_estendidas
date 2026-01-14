<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Barcode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Remove espaços, aceita apenas números e verifica tamanhos comuns (8, 12, 13 dígitos)
        if (!preg_match('/^\d{8,14}$/', $value)) {
            $fail('O código de barras deve conter apenas números (8 a 14 dígitos).');
            return;
        }

        if (!$this->isValidChecksum($value)) {
            $fail('O código de barras informado é inválido (dígito verificador incorreto).');
        }
    }

    private function isValidChecksum($barcode): bool
    {
        $digits = str_split(strrev($barcode));
        $checksum = 0;

        foreach ($digits as $index => $digit) {
            // Se a posição for par (baseado em 0, então 0, 2, 4...), multiplica por 3, senão por 1
            $checksum += $index % 2 === 0 ? $digit : $digit * 3;
        }

        return $checksum % 10 === 0;
    }
}