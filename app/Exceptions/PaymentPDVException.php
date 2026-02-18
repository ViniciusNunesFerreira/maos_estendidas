<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class PaymentPDVException extends Exception
{
    protected array $context = [];

    public function __construct(
        string $message = "",
        int $code = 422,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get exception context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Render exception as JSON response
     */
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => 'PAYMENT_ERROR',
            'context' => $this->context,
        ], $this->getCode() ?: 422);
    }
}
