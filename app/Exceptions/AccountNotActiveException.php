<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class AccountNotActiveException extends Exception
{
    protected string $errorCode;

    public function __construct(
        string $message = "Sua conta está inativa.",
        string $errorCode = 'ACCOUNT_INACTIVE',
        int $code = 403,
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