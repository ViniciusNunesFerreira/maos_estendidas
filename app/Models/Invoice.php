<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;
    use Auditable;

    protected $fillable = [
        'filho_id',
        'invoice_number',
        'type', // 'consumption', 'subscription'
        'period_start',
        'period_end',
        'issue_date',
        'due_date',
        'is_avulse',
        // Valores
        'subtotal',
        'discount_amount',
        'discount_reason',
        'late_fee', // Multa
        'interest', // Juros
        'late_fee_applied',
        'total_amount',
        'paid_amount',
        'tax_amount',
        // Status e Controle
        'status',
        'overdue_at',
        'paid_at',
        'cancelled_at',
        'cancellation_reason',
        'notification_sent',
        'notification_sent_at',
        'reminder_sent',
        'reminder_sent_at',
        'overdue_notice_sent',
        'overdue_notice_sent_at',
        // Notas
        'notes',
        'internal_notes',
        'error_message',
        'retry_count',
        // Fiscal
        'sat_number',
        'sat_key',
        'sat_qrcode',
        'sat_xml',
        'subscription_id'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'interest' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'late_fee_applied' => 'boolean',
        'overdue_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
        'reminder_sent' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'overdue_notice_sent' => 'boolean',
        'overdue_notice_sent_at' => 'datetime',
        'fiscal' => 'array'
    ];

    protected $attributes = [
        'type' => 'consumption',
        'status' => 'draft',
        'subtotal' => 0,
        'discount_amount' => 0,
        'late_fee' => 0,
        'interest' => 0,
        'total_amount' => 0,
        'paid_amount' => 0,
        'notification_sent' => false,
    ];

    protected $appends = ['remaining_amount', 'is_paid', 'is_overdue', 'days_overdue', 'status_label'];

    // =========================================================
    // RELACIONAMENTOS
    // =========================================================

    public function filho(): BelongsTo
    {
        return $this->belongsTo(Filho::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Pedidos vinculados a esta fatura
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    // =========================================================
    // ACCESSORS & COMPUTED
    // =========================================================

    public function getRemainingAmountAttribute(): float
    {
        // Garante que não retorne negativo por erro de arredondamento
        return max(0, $this->total_amount - $this->paid_amount);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid' && $this->remaining_amount <= 0.99;
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->is_paid || $this->status === 'cancelled') {
            return false;
        }
        return $this->status === 'overdue' || ($this->due_date < now()->startOfDay());
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return (int) $this->due_date->diffInDays(now(), false); // false permite negativo se necessário, mas a lógica acima barra
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Rascunho',
            'pending' => 'Pendente',
            'processing' => 'Processando',
            'paid' => 'Paga',
            'overdue' => 'Vencida',
            'cancelled' => 'Cancelada',
            'partial' => 'Parcial',
            'failed' => 'Falha',
            default => 'Desconhecido',
        };
    }

    
    // =========================================================
    // SCOPES
    // =========================================================

    public function scopePaid($query)
    {
        return $query->where('status', 'paid')->orWhere('status', 'partial');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'partial', 'open', 'overdue']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->whereIn('status', ['pending', 'partial', 'open'])
                  ->where('due_date', '<', now()->startOfDay());
            });
    }

    public function scopeTypeConsumption($query)
    {
        return $query->where('type', 'consumption');
    }

    public function scopeTypeSubscription($query)
    {
        return $query->where('type', 'subscription');
    }

    // =========================================================
    // MÉTODOS DE NEGÓCIO
    // =========================================================

    /**
     * Gera número sequencial de fatura seguro
     */
    public static function generateNextInvoiceNumber(string $type = 'consumption'): string
    {
        $prefix = $type === 'subscription' ? 'FAT-ASSIN' : 'FAT-LOJA';
        $ym = now()->format('Ym');
        $base = "{$prefix}-{$ym}-";

        // Busca a última fatura deste tipo e mês para incrementar
        $lastInvoice = static::where('invoice_number', 'LIKE', "{$base}%")
            ->orderByRaw('LENGTH(invoice_number) DESC')
            ->orderBy('invoice_number', 'DESC')
            ->first();

        if ($lastInvoice) {
            // Extrai a sequência final
            $parts = explode('-', $lastInvoice->invoice_number);
            $seq = (int) end($parts);
            $nextSeq = $seq + 1;
        } else {
            $nextSeq = 1;
        }

        return $base . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Recalcula totais baseado nos itens
     */
    public function recalculateTotals(): void
    {
        $this->loadMissing('items');
        
        $subtotal = $this->items->sum('subtotal');
        $itemsDiscount = $this->items->sum('discount_amount'); // Descontos aplicados nos itens
        
        // Base de cálculo
        $baseTotal = $subtotal - $itemsDiscount;
        
        // Aplica descontos globais da fatura
        $baseTotal -= $this->discount_amount;

        // Soma multas e juros
        $finalTotal = $baseTotal + $this->late_fee + $this->interest;
        
        $this->update([
            'subtotal' => $subtotal,
            'total_amount' => max(0, $finalTotal), // Nunca negativo
        ]);
        
        // Atualiza status se já estiver pago
        if ($this->paid_amount >= $this->total_amount && $this->total_amount > 0) {
            $this->update(['status' => 'paid', 'paid_at' => $this->paid_at ?? now()]);
        } elseif ($this->paid_amount > 0) {
            $this->update(['status' => 'partial']);
        }
    }


    // =========================================================
    // MÉTODOS DE INSTÂNCIA
    // =========================================================

    public function markAsPaid(float $amount = null): void
    {
        $paidAmount = $amount ?? $this->remaining_amount;
        
        $this->update([
            'paid_amount' => $this->paid_amount + $paidAmount,
            'status' => ($this->paid_amount + $paidAmount) >= $this->total_amount ? 'paid' : 'partial',
            'paid_at' => ($this->paid_amount + $paidAmount) >= $this->total_amount ? now() : null,
        ]);
    }

    public function markAsOverdue(): void
    {
        if ($this->status !== 'paid' && $this->due_date < now()) {
            $this->update([
                'status' => 'overdue',
                'overdue_at' => $this->overdue_at ?? now(),
            ]);
        }
    }

    public function cancel(string $reason): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        Order::where('invoice_id', $this->id)->update(['invoice_id' => null]);
    }
}