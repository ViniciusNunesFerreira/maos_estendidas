<?php
// app/Exceptions/InvalidCredentialsException.php

namespace App\Exceptions;

use Exception;
use Throwable;


class InvalidCredentialsException extends Exception
{
    

    protected string $errorCode;

    public function __construct(
        string $message = "Credenciais Inválidas.",
        string $errorCode = 'INVALID_CREDENTIALS',
        int $code = 401,
        ?Throwable $previous = null
    ) {
        // Armazena a string em uma propriedade separada
        $this->errorCode = $errorCode;

        // Passa apenas o inteiro ($code) para o construtor pai
        parent::__construct($message, $code, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
