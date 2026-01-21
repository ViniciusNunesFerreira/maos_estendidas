<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;
    use Auditable;

    protected $fillable = [
        'order_number',
        'filho_id',
        'created_by_user_id',
        'user_id', // Operador
        'invoice_id', // Vínculo com fatura mensal
        'origin', // pdv, totem, app
        'device_id',
        
        // Dados Cliente
        'customer_type',
        'customer_name',
        'customer_cpf',
        'customer_phone',
        'guest_name',
        'guest_document',

        // Valores
        'subtotal',
        'discount',
        'total',
        
        // Status
        'status',
        'cancellation_reason',
        'cancelled_at',
        
        // Faturamento
        'is_invoiced',
        'invoiced_at',
        
        // Timestamps Processo
        'paid_at',
        'preparing_at',
        'ready_at',
        'delivered_at',
        'completed_at',
        
        // Sincronização e Notas
        'is_synced',
        'synced_at',
        'sync_uuid',
        'notes',
        'kitchen_notes',

        'payment_intent_id',
        'awaiting_external_payment',
        'payment_method_chosen'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_invoiced' => 'boolean',
        'invoiced_at' => 'datetime',
        'is_synced' => 'boolean',
    ];

    // =========================================================
    // RELACIONAMENTOS
    // =========================================================

    public function filho(): BelongsTo
    {
        return $this->belongsTo(Filho::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Fatura a qual este pedido pertence (Fechamento Mensal)
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }


    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class, 'payment_intent_id');
    }

    public function isPaid(): bool
    {
        // Verifique qual coluna define o pagamento no seu banco. 
        // Geralmente é o status ou a existência da data de pagamento.
        return $this->status === 'paid' || $this->paid_at !== null;
    }


    // =========================================================
    // SCOPES
    // =========================================================

    /**
     * Pedidos elegíveis para faturamento (fechamento mensal)
     */
    public function scopeEligibleForInvoicing($query)
    {
        return $query->where('customer_type', 'filho')
                     ->whereNotNull('filho_id')
                     ->where('is_invoiced', false)
                     ->where('status', '!=', 'cancelled')
                     ->whereNull('invoice_id'); // Garante que não está vinculado
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForFilhos($query)
    {
        return $query->where('customer_type', 'filho');
    }

    public function scopeForGuests($query)
    {
        return $query->where('customer_type', 'guest');
    }


    // =========================================================
    // ACCESSORS
    // =========================================================

    /**
     * Se é venda para visitante
     */
    public function getIsGuestAttribute(): bool
    {
        return $this->customer_type === 'guest';
    }

    /**
     * Se é venda para filho
     */
    public function getIsFilhoAttribute(): bool
    {
        return $this->customer_type === 'filho';
    }

    // Métodos de negócio
    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function markAsPreparing(): void
    {
        $this->update([
            'status' => 'preparing',
            'preparing_at' => now(),
        ]);
    }

    public function markAsReady(): void
    {
        $this->update([
            'status' => 'ready',
            'ready_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Marcar como faturado
     */
    public function markAsInvoiced(Invoice $invoice): void
    {
        $this->update([
            'invoice_id' => $invoice->id,
            'is_invoiced' => true,
            'invoiced_at' => now(),
        ]);
    }

    public function cancel(string $reason): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Calcular totais
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum(fn($item) => $item->subtotal);
        
        $this->update([
            'subtotal' => $subtotal,
            'total_amount' => $subtotal - $this->discount_amount,
        ]);
    }
}