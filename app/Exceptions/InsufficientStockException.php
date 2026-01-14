<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception para estoque insuficiente
 */
class InsufficientStockException extends Exception
{
    protected $code = 422;
    
    public function __construct(string $message = 'Estoque insuficiente')
    {
        parent::__construct($message, $this->code);
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => 'INSUFFICIENT_STOCK',
        ], $this->code);
    }
}
