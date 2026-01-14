<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class InsufficientCreditException extends Exception
{
    protected $code = 400;

    protected string $errorCode;

    public function __construct(
        string $message = "Crédito Insulficiente.",
        string $errorCode = 'INSUFICIENT_CREDIT',
        int $code = 400,
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