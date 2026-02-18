<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GetnetTransaction extends Model
{
    use HasFactory;
    use HasUuid;
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'payment_intent_id',
        'order_id',
        'point_device_id',
        'terminal_id',
        'payment_id',
        'seller_id',
        'terminal_serial_number',
        'payment_method',
        'amount',
        'currency',
        'installments',
        'status',
        'status_detail',
        'denial_reason',
        'nsu',
        'authorization_code',
        'acquirer_transaction_id',
        'terminal_nsu',
        'card_brand',
        'card_last_digits',
        'card_holder_name',
        'pix_qr_code',
        'pix_qr_code_base64',
        'pix_txid',
        'pix_expiration',
        'api_request',
        'api_response',
        'webhook_payload',
        'attempts',
        'last_error',
        'last_attempt_at',
        'sent_to_terminal_at',
        'terminal_received_at',
        'customer_interaction_at',
        'processed_at',
        'approved_at',
        'denied_at',
        'cancelled_at',
        'pdv_device_id',
        'operator_name',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'installments' => 'integer',
        'api_request' => 'array',
        'api_response' => 'array',
        'webhook_payload' => 'array',
        'metadata' => 'array',
        'attempts' => 'integer',
        'pix_expiration' => 'datetime',
        'last_attempt_at' => 'datetime',
        'sent_to_terminal_at' => 'datetime',
        'terminal_received_at' => 'datetime',
        'customer_interaction_at' => 'datetime',
        'processed_at' => 'datetime',
        'approved_at' => 'datetime',
        'denied_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // =========================================================
    // RELACIONAMENTOS
    // =========================================================

    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function pointDevice(): BelongsTo
    {
        return $this->belongsTo(PointDevice::class);
    }

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopePending($query)
    {
        return $query->whereIn('status', ['created', 'pending', 'waiting_terminal', 'processing']);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeDenied($query)
    {
        return $query->where('status', 'denied');
    }

    public function scopeFinalized($query)
    {
        return $query->whereIn('status', ['approved', 'denied', 'cancelled', 'error']);
    }

    public function scopeAwaitingTerminal($query)
    {
        return $query->where('status', 'waiting_terminal');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeByTerminal($query, string $terminalId)
    {
        return $query->where('terminal_id', $terminalId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopePix($query)
    {
        return $query->where('payment_method', 'pix');
    }

    public function scopeCard($query)
    {
        return $query->whereIn('payment_method', ['credit_card', 'debit_card']);
    }

    // =========================================================
    // ACCESSORS
    // =========================================================

    /**
     * Verifica se é PIX
     */
    public function getIsPixAttribute(): bool
    {
        return $this->payment_method === 'pix';
    }

    /**
     * Verifica se é cartão
     */
    public function getIsCardAttribute(): bool
    {
        return in_array($this->payment_method, ['credit_card', 'debit_card']);
    }

    /**
     * Verifica se está pendente
     */
    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, ['created', 'pending', 'waiting_terminal', 'processing']);
    }

    /**
     * Verifica se foi aprovado
     */
    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Verifica se foi negado
     */
    public function getIsDeniedAttribute(): bool
    {
        return $this->status === 'denied';
    }

    /**
     * Verifica se foi cancelado
     */
    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Verifica se está finalizado
     */
    public function getIsFinalizedAttribute(): bool
    {
        return in_array($this->status, ['approved', 'denied', 'cancelled', 'error']);
    }

    /**
     * Tempo decorrido desde criação (em segundos)
     */
    public function getElapsedTimeAttribute(): int
    {
        return now()->diffInSeconds($this->created_at);
    }

    /**
     * Verifica se está em timeout
     */
    public function getIsTimedOutAttribute(): bool
    {
        // Se passou mais de 5 minutos e ainda está pendente
        return $this->is_pending && $this->elapsed_time > 300;
    }

    /**
     * Mensagem amigável do status
     */
    public function getStatusMessageAttribute(): string
    {
        return match($this->status) {
            'created' => 'Transação criada',
            'pending' => 'Aguardando processamento',
            'waiting_terminal' => 'Aguardando terminal',
            'processing' => 'Processando pagamento',
            'approved' => 'Pagamento aprovado',
            'denied' => 'Pagamento negado: ' . ($this->denial_reason ?? 'Motivo não informado'),
            'cancelled' => 'Pagamento cancelado',
            'error' => 'Erro: ' . ($this->last_error ?? 'Erro desconhecido'),
            'timeout' => 'Tempo esgotado',
            default => 'Status desconhecido',
        };
    }

    // =========================================================
    // MÉTODOS DE NEGÓCIO
    // =========================================================

    /**
     * Marca como enviado ao terminal
     */
    public function markAsSentToTerminal(): void
    {
        $this->update([
            'status' => 'waiting_terminal',
            'sent_to_terminal_at' => now(),
        ]);
    }

    /**
     * Marca que terminal recebeu
     */
    public function markAsReceivedByTerminal(): void
    {
        $this->update([
            'status' => 'processing',
            'terminal_received_at' => now(),
        ]);
    }

    /**
     * Marca interação do cliente
     */
    public function markAsCustomerInteraction(): void
    {
        $this->update([
            'customer_interaction_at' => now(),
        ]);
    }

    /**
     * Marca como aprovado
     */
    public function markAsApproved(array $data = []): void
    {
        $updateData = [
            'status' => 'approved',
            'approved_at' => now(),
            'processed_at' => now(),
        ];

        // Adiciona dados de autorização se fornecidos
        if (isset($data['nsu'])) {
            $updateData['nsu'] = $data['nsu'];
        }
        if (isset($data['authorization_code'])) {
            $updateData['authorization_code'] = $data['authorization_code'];
        }
        if (isset($data['acquirer_transaction_id'])) {
            $updateData['acquirer_transaction_id'] = $data['acquirer_transaction_id'];
        }
        if (isset($data['card_brand'])) {
            $updateData['card_brand'] = $data['card_brand'];
        }
        if (isset($data['card_last_digits'])) {
            $updateData['card_last_digits'] = $data['card_last_digits'];
        }

        $this->update($updateData);
    }

    /**
     * Marca como negado
     */
    public function markAsDenied(string $reason, string $statusDetail = null): void
    {
        $this->update([
            'status' => 'denied',
            'denial_reason' => $reason,
            'status_detail' => $statusDetail,
            'denied_at' => now(),
            'processed_at' => now(),
        ]);
    }

    /**
     * Marca como cancelado
     */
    public function markAsCancelled(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'status_detail' => $reason,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Marca como erro
     */
    public function markAsError(string $error): void
    {
        $this->update([
            'status' => 'error',
            'last_error' => $error,
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * Marca como timeout
     */
    public function markAsTimeout(): void
    {
        $this->update([
            'status' => 'timeout',
            'last_error' => 'Tempo limite de espera excedido',
        ]);
    }

    /**
     * Incrementa tentativas
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
        $this->update(['last_attempt_at' => now()]);
    }

    /**
     * Registra request da API
     */
    public function recordApiRequest(array $request): void
    {
        $this->update(['api_request' => $request]);
    }

    /**
     * Registra response da API
     */
    public function recordApiResponse(array $response): void
    {
        $this->update(['api_response' => $response]);
    }

    /**
     * Registra payload do webhook
     */
    public function recordWebhookPayload(array $payload): void
    {
        $this->update(['webhook_payload' => $payload]);
    }

    /**
     * Verifica se pode ser cancelado
     */
    public function canBeCancelled(): bool
    {
        return $this->is_pending;
    }

    /**
     * Verifica se pode ser retentado
     */
    public function canBeRetried(): bool
    {
        return in_array($this->status, ['error', 'timeout']) && $this->attempts < 3;
    }

    /**
     * Obtém dados formatados para resposta API
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'status' => $this->status,
            'status_message' => $this->status_message,
            'payment_method' => $this->payment_method,
            'amount' => $this->amount,
            'terminal_id' => $this->terminal_id,
            'nsu' => $this->nsu,
            'authorization_code' => $this->authorization_code,
            'card_brand' => $this->card_brand,
            'card_last_digits' => $this->card_last_digits,
            'pix_qr_code' => $this->pix_qr_code,
            'pix_qr_code_base64' => $this->pix_qr_code_base64,
            'pix_txid' => $this->pix_txid,
            'created_at' => $this->created_at->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
        ];
    }
}