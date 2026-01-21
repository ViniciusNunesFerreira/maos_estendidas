<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentIntent extends Model
{
    use HasFactory;
    use HasUuid;
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'payment_id',
        'mp_payment_id',
        'mp_payment_intent_id',
        'integration_type',
        'payment_method',
        'amount',
        'amount_paid',
        'status',
        'status_detail',
        'pix_qr_code',
        'pix_qr_code_base64',
        'pix_ticket_url',
        'pix_expiration',
        'tef_device_id',
        'tef_external_reference',
        'tef_print_on_terminal',
        'card_token',
        'card_last_digits',
        'card_brand',
        'installments',
        'mp_request',
        'mp_response',
        'attempts',
        'last_error',
        'last_attempt_at',
        'created_at_mp',
        'approved_at',
        'rejected_at',
        'cancelled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'pix_expiration' => 'datetime',
        'tef_print_on_terminal' => 'boolean',
        'installments' => 'integer',
        'mp_request' => 'array',
        'mp_response' => 'array',
        'attempts' => 'integer',
        'last_attempt_at' => 'datetime',
        'created_at_mp' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // =========================================================
    // RELACIONAMENTOS
    // =========================================================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCheckout($query)
    {
        return $query->where('integration_type', 'checkout');
    }

    public function scopePointTef($query)
    {
        return $query->where('integration_type', 'point_tef');
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
     * Se é PIX
     */
    public function getIsPixAttribute(): bool
    {
        return $this->payment_method === 'pix';
    }

    /**
     * Se é cartão
     */
    public function getIsCardAttribute(): bool
    {
        return in_array($this->payment_method, ['credit_card', 'debit_card']);
    }

    /**
     * Se é TEF
     */
    public function getIsTefAttribute(): bool
    {
        return $this->integration_type === 'point_tef';
    }

    /**
     * Se está pendente
     */
    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, ['created', 'pending', 'processing']);
    }

    /**
     * Se está aprovado
     */
    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Se foi rejeitado
     */
    public function getIsRejectedAttribute(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Se foi cancelado
     */
    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Se houve erro
     */
    public function getHasErrorAttribute(): bool
    {
        return $this->status === 'error';
    }

    // =========================================================
    // MÉTODOS DE NEGÓCIO
    // =========================================================

    /**
     * Marcar como aprovado
     */
    public function markAsApproved(?float $amountPaid = null): void
    {
        $this->update([
            'status' => 'approved',
            'amount_paid' => $amountPaid ?? $this->amount,
            'approved_at' => now(),
        ]);
    }

    /**
     * Marcar como rejeitado
     */
    public function markAsRejected(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'status_detail' => $reason,
            'rejected_at' => now(),
        ]);
    }

    /**
     * Marcar como cancelado
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
     * Marcar como erro
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
     * Incrementar tentativas
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
        $this->update(['last_attempt_at' => now()]);
    }

    /**
     * Verificar se PIX expirou
     */
    public function isPixExpired(): bool
    {
        if (!$this->is_pix || !$this->pix_expiration) {
            return false;
        }

        return now()->isAfter($this->pix_expiration);
    }

    /**
     * Obter mensagem de status amigável
     */
    public function getStatusMessage(): string
    {
        return match($this->status) {
            'created' => 'Payment intent criado',
            'pending' => 'Aguardando pagamento',
            'processing' => 'Processando pagamento',
            'approved' => 'Pagamento aprovado',
            'rejected' => 'Pagamento rejeitado: ' . $this->status_detail,
            'cancelled' => 'Pagamento cancelado',
            'refunded' => 'Pagamento estornado',
            'error' => 'Erro ao processar: ' . $this->last_error,
            default => 'Status desconhecido',
        };
    }

    /**
     * Dados do QR Code PIX para resposta
     */
    public function getPixData(): ?array
    {
        if (!$this->is_pix) {
            return null;
        }

        return [
            'qr_code' => $this->pix_qr_code,
            'qr_code_base64' => $this->pix_qr_code_base64,
            'ticket_url' => $this->pix_ticket_url,
            'expiration' => $this->pix_expiration?->toIso8601String(),
            'is_expired' => $this->isPixExpired(),
        ];
    }
}