<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Exception customizada para erros do Mercado Pago
 */
class MercadoPagoException extends Exception
{
    protected ?array $mercadoPagoResponse = null;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
        ?array $mercadoPagoResponse = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->mercadoPagoResponse = $mercadoPagoResponse;
    }

    /**
     * Obter resposta completa do Mercado Pago
     */
    public function getMercadoPagoResponse(): ?array
    {
        return $this->mercadoPagoResponse;
    }

    /**
     * Verificar se tem detalhes do MP
     */
    public function hasMercadoPagoResponse(): bool
    {
        return !empty($this->mercadoPagoResponse);
    }

    /**
     * Obter código de erro do MP
     */
    public function getMercadoPagoErrorCode(): ?string
    {
        return $this->mercadoPagoResponse['cause'][0]['code'] ?? null;
    }

    /**
     * Obter descrição do erro do MP
     */
    public function getMercadoPagoErrorDescription(): ?string
    {
        return $this->mercadoPagoResponse['cause'][0]['description'] ?? null;
    }

    /**
     * Renderizar para API response
     */
    public function toApiResponse(): array
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => 'MERCADOPAGO_ERROR',
        ];

        if ($this->hasMercadoPagoResponse()) {
            $response['mp_error_code'] = $this->getMercadoPagoErrorCode();
            $response['mp_error_description'] = $this->getMercadoPagoErrorDescription();
        }

        return $response;
    }
}