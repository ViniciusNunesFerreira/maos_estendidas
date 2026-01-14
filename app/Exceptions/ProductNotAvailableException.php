<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception para produto não disponível
 */
class ProductNotAvailableException extends Exception
{
    protected $code = 422;
    
    public function __construct(string $message = 'Produto não disponível')
    {
        parent::__construct($message, $this->code);
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => 'PRODUCT_NOT_AVAILABLE',
        ], $this->code);
    }
}