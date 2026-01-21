<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhook extends Model
{
    use HasFactory;
    use HasUuid;
    use Auditable;

    protected $fillable = [
        'gateway',
        'event_type',
        'action',
        'mp_payment_id',
        'mp_merchant_order_id',
        'order_id',
        'payment_id',
        'payment_intent_id',
        'payload',
        'headers',
        'signature_valid',
        'signature',
        'ip_address',
        'status',
        'processing_error',
        'processed_at',
        'retry_count',
        'next_retry_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'signature_valid' => 'boolean',
        'processed_at' => 'datetime',
        'retry_count' => 'integer',
        'next_retry_at' => 'datetime',
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

    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class);
    }

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopePending($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForRetry($query)
    {
        return $query->where('status', 'failed')
            ->where('retry_count', '<', 3)
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                  ->orWhere('next_retry_at', '<=', now());
            });
    }

    // =========================================================
    // MÉTODOS DE NEGÓCIO
    // =========================================================

    /**
     * Marcar como processado
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Marcar como falhou
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'processing_error' => $error,
            'processed_at' => now(),
        ]);
    }

    /**
     * Marcar como ignorado
     */
    public function markAsIgnored(string $reason): void
    {
        $this->update([
            'status' => 'ignored',
            'processing_error' => $reason,
            'processed_at' => now(),
        ]);
    }

    /**
     * Agendar retry
     */
    public function scheduleRetry(int $delayMinutes = 5): void
    {
        $this->increment('retry_count');
        $this->update([
            'next_retry_at' => now()->addMinutes($delayMinutes * $this->retry_count),
        ]);
    }

    /**
     * Obter tipo de evento formatado
     */
    public function getEventTypeName(): string
    {
        return match($this->event_type) {
            'payment.created' => 'Pagamento criado',
            'payment.updated' => 'Pagamento atualizado',
            'merchant_order' => 'Pedido atualizado',
            default => $this->event_type,
        };
    }
}