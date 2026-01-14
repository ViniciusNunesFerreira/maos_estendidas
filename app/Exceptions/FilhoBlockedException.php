<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception para filho bloqueado
 */
class FilhoBlockedException extends Exception
{
    protected $code = 403;
    
    public function __construct(string $message = 'Usuário bloqueado')
    {
        parent::__construct($message, $this->code);
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => 'FILHO_BLOCKED',
        ], $this->code);
    }
}