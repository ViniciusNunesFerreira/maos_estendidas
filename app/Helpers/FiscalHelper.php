<?php
// app/Helpers/FiscalHelper.php

namespace App\Helpers;

class FiscalHelper
{
    public static function formatCnpj(string $cnpj): string
    {
        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $cnpj);
    }

    public static function validateCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) != 14) {
            return false;
        }

        // Add CNPJ validation logic here
        return true;
    }
}