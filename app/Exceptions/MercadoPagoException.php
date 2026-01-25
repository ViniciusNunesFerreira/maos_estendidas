<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception customizada para erros do Mercado Pago
 * 
 * Lançada quando:
 * - Falha na comunicação com API do MP
 * - Resposta inválida do MP
 * - Configuração incorreta
 * - Erro ao processar pagamento
 */
class MercadoPagoException extends Exception
{
    /**
     * Código de erro do Mercado Pago (se disponível)
     */
    protected ?string $mpErrorCode = null;

    /**
     * Status HTTP da resposta do MP
     */
    protected ?int $httpStatus = null;

    /**
     * Resposta completa do MP para debug
     */
    protected ?array $mpResponse = null;

    /**
     * Criar nova exception do Mercado Pago
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Exception $previous = null,
        ?string $mpErrorCode = null,
        ?int $httpStatus = null,
        ?array $mpResponse = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->mpErrorCode = $mpErrorCode;
        $this->httpStatus = $httpStatus;
        $this->mpResponse = $mpResponse;
    }

    /**
     * Obter código de erro do MP
     */
    public function getMpErrorCode(): ?string
    {
        return $this->mpErrorCode;
    }

    /**
     * Obter status HTTP
     */
    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    /**
     * Obter resposta completa do MP
     */
    public function getMpResponse(): ?array
    {
        return $this->mpResponse;
    }

    /**
     * Helper: criar exception de configuração inválida
     */
    public static function invalidConfiguration(string $message): self
    {
        return new self(
            message: "Configuração do Mercado Pago inválida: {$message}",
            code: 500
        );
    }

    /**
     * Helper: criar exception de falha na API
     */
    public static function apiFailure(
        string $message,
        ?string $mpErrorCode = null,
        ?int $httpStatus = null,
        ?array $mpResponse = null
    ): self {
        return new self(
            message: $message,
            code: $httpStatus ?? 500,
            mpErrorCode: $mpErrorCode,
            httpStatus: $httpStatus,
            mpResponse: $mpResponse
        );
    }

    /**
     * Helper: criar exception de resposta inválida
     */
    public static function invalidResponse(string $message, ?array $response = null): self
    {
        return new self(
            message: "Resposta inválida do Mercado Pago: {$message}",
            code: 500,
            mpResponse: $response
        );
    }
}