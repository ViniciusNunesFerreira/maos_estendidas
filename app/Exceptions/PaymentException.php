<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception genérica para erros de pagamento
 * 
 * Lançada quando:
 * - Falha ao processar pagamento (qualquer método)
 * - Dados de pagamento inválidos
 * - Pedido/Fatura não pode ser pago
 * - Erro em gateway de pagamento
 * - Validação de pagamento falha
 * - Status de pagamento inválido
 * 
 * @version 1.0
 */
class PaymentException extends Exception
{
    /**
     * Código de erro interno (para tracking)
     */
    protected ?string $errorCode = null;

    /**
     * Método de pagamento que falhou
     */
    protected ?string $paymentMethod = null;

    /**
     * ID da entidade relacionada (Order/Invoice)
     */
    protected ?string $entityId = null;

    /**
     * Tipo da entidade (order/invoice)
     */
    protected ?string $entityType = null;

    /**
     * Dados adicionais do erro
     */
    protected ?array $errorData = null;

    /**
     * Criar nova exception de pagamento
     */
    public function __construct(
        string $message = "",
        int $code = 422,
        ?Exception $previous = null,
        ?string $errorCode = null,
        ?string $paymentMethod = null,
        ?string $entityId = null,
        ?string $entityType = null,
        ?array $errorData = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->errorCode = $errorCode;
        $this->paymentMethod = $paymentMethod;
        $this->entityId = $entityId;
        $this->entityType = $entityType;
        $this->errorData = $errorData;
    }

    /**
     * Obter código de erro
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Obter método de pagamento
     */
    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    /**
     * Obter ID da entidade
     */
    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    /**
     * Obter tipo da entidade
     */
    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    /**
     * Obter dados adicionais
     */
    public function getErrorData(): ?array
    {
        return $this->errorData;
    }

    /**
     * Converter para array (para JSON response)
     */
    public function toArray(): array
    {
        return [
            'error' => 'payment_error',
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
            'payment_method' => $this->paymentMethod,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'error_data' => $this->errorData,
        ];
    }


    public function render($request)
    {
        return response()->json($this->toArray(), $this->getCode() ?: 422);
    }

    // ====================================================================
    // HELPER METHODS - Criar exceptions específicas
    // ====================================================================

    /**
     * Pagamento já processado
     */
    public static function alreadyPaid(string $entityType, string $entityId): self
    {
        return new self(
            message: ucfirst($entityType) . " já foi pago anteriormente",
            code: 422,
            errorCode: 'already_paid',
            entityType: $entityType,
            entityId: $entityId
        );
    }

    /**
     * Entidade não pode ser paga
     */
    public static function cannotBePaid(string $entityType, string $entityId, string $reason): self
    {
        return new self(
            message: ucfirst($entityType) . " não pode ser pago: {$reason}",
            code: 422,
            errorCode: 'cannot_be_paid',
            entityType: $entityType,
            entityId: $entityId,
            errorData: ['reason' => $reason]
        );
    }

    /**
     * Método de pagamento inválido
     */
    public static function invalidMethod(string $method, ?string $reason = null): self
    {
        $message = "Método de pagamento '{$method}' é inválido";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self(
            message: $message,
            code: 422,
            errorCode: 'invalid_payment_method',
            paymentMethod: $method,
            errorData: $reason ? ['reason' => $reason] : null
        );
    }

    /**
     * Método não suportado para esta entidade
     */
    public static function methodNotSupported(
        string $method, 
        string $entityType, 
        ?array $supportedMethods = null
    ): self {
        $message = "Método '{$method}' não é suportado para {$entityType}";
        
        if ($supportedMethods) {
            $message .= ". Métodos aceitos: " . implode(', ', $supportedMethods);
        }

        return new self(
            message: $message,
            code: 422,
            errorCode: 'method_not_supported',
            paymentMethod: $method,
            entityType: $entityType,
            errorData: $supportedMethods ? ['supported_methods' => $supportedMethods] : null
        );
    }

    /**
     * Dados de pagamento inválidos
     */
    public static function invalidData(string $message, ?array $fields = null): self
    {
        return new self(
            message: $message,
            code: 422,
            errorCode: 'invalid_payment_data',
            errorData: $fields ? ['invalid_fields' => $fields] : null
        );
    }

    /**
     * Falha ao processar pagamento
     */
    public static function processingFailed(
        string $method, 
        string $message, 
        ?array $details = null
    ): self {
        return new self(
            message: "Falha ao processar pagamento via {$method}: {$message}",
            code: 500,
            errorCode: 'processing_failed',
            paymentMethod: $method,
            errorData: $details
        );
    }

    /**
     * Valor inválido
     */
    public static function invalidAmount(float $amount, ?string $reason = null): self
    {
        $message = "Valor de pagamento inválido: R$ " . number_format($amount, 2, ',', '.');
        if ($reason) {
            $message .= " - {$reason}";
        }

        return new self(
            message: $message,
            code: 422,
            errorCode: 'invalid_amount',
            errorData: ['amount' => $amount, 'reason' => $reason]
        );
    }

    /**
     * Timeout no processamento
     */
    public static function timeout(string $method, int $seconds): self
    {
        return new self(
            message: "Timeout ao processar pagamento via {$method} (aguardou {$seconds}s)",
            code: 504,
            errorCode: 'payment_timeout',
            paymentMethod: $method,
            errorData: ['timeout_seconds' => $seconds]
        );
    }

    /**
     * Pagamento cancelado
     */
    public static function cancelled(
        string $entityType, 
        string $entityId, 
        ?string $reason = null
    ): self {
        $message = ucfirst($entityType) . " teve o pagamento cancelado";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self(
            message: $message,
            code: 422,
            errorCode: 'payment_cancelled',
            entityType: $entityType,
            entityId: $entityId,
            errorData: $reason ? ['reason' => $reason] : null
        );
    }

    /**
     * Reembolso não disponível
     */
    public static function refundNotAvailable(string $paymentId, string $reason): self
    {
        return new self(
            message: "Reembolso não disponível: {$reason}",
            code: 422,
            errorCode: 'refund_not_available',
            entityId: $paymentId,
            errorData: ['reason' => $reason]
        );
    }

    /**
     * Status de pagamento inválido
     */
    public static function invalidStatus(string $currentStatus, array $allowedStatuses): self
    {
        return new self(
            message: "Status atual '{$currentStatus}' não permite esta operação. " .
                     "Status permitidos: " . implode(', ', $allowedStatuses),
            code: 422,
            errorCode: 'invalid_payment_status',
            errorData: [
                'current_status' => $currentStatus,
                'allowed_statuses' => $allowedStatuses
            ]
        );
    }

    /**
     * Falha na validação de segurança
     */
    public static function securityValidationFailed(string $reason): self
    {
        return new self(
            message: "Validação de segurança falhou: {$reason}",
            code: 403,
            errorCode: 'security_validation_failed',
            errorData: ['reason' => $reason]
        );
    }

    /**
     * Gateway de pagamento não disponível
     */
    public static function gatewayUnavailable(string $gateway, ?string $message = null): self
    {
        $msg = "Gateway de pagamento '{$gateway}' não está disponível";
        if ($message) {
            $msg .= ": {$message}";
        }

        return new self(
            message: $msg,
            code: 503,
            errorCode: 'gateway_unavailable',
            errorData: ['gateway' => $gateway, 'details' => $message]
        );
    }

    /**
     * PIX expirado
     */
    public static function pixExpired(string $paymentIntentId): self
    {
        return new self(
            message: "QR Code PIX expirou. Por favor, gere um novo.",
            code: 422,
            errorCode: 'pix_expired',
            paymentMethod: 'pix',
            entityId: $paymentIntentId
        );
    }

    /**
     * Cartão recusado
     */
    public static function cardDeclined(string $reason, ?array $cardDetails = null): self
    {
        return new self(
            message: "Cartão recusado: {$reason}",
            code: 422,
            errorCode: 'card_declined',
            paymentMethod: 'credit_card',
            errorData: array_merge(
                ['reason' => $reason],
                $cardDetails ?? []
            )
        );
    }
}
