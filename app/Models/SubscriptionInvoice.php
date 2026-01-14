<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionInvoice extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'subscription_id',
        'filho_id',
        'invoice_number',
        'period_start',
        'period_end',
        'issue_date',
        'due_date',
        'amount',
        'discount',
        'discount_reason',
        'total',
        'paid_amount',
        'status',
        'paid_at',
        'overdue_at',
        'cancelled_at',
        'cancellation_reason',
        'notification_sent',
        'reminder_sent',
        'overdue_notice_sent',
        'payment_method',
        'payment_reference',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'issue_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'overdue_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'notification_sent' => 'boolean',
        'reminder_sent' => 'boolean',
        'overdue_notice_sent' => 'boolean',
    ];

    // =========================================================
    // RELACIONAMENTOS
    // =========================================================

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function filho(): BelongsTo
    {
        return $this->belongsTo(Filho::class);
    }

    // =========================================================
    // MÉTODOS ESTÁTICOS
    // =========================================================

    public static function generateNumber(): string
    {
        $prefix = 'FAT-ASSIN';
        $yearMonth = now()->format('Ym');
        
        $lastNumber = static::where('invoice_number', 'like', "{$prefix}-{$yearMonth}-%")
            ->orderByDesc('invoice_number')
            ->value('invoice_number');
        
        if ($lastNumber) {
            $lastSeq = (int) substr($lastNumber, -4);
            $newSeq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newSeq = '0001';
        }
        
        return "{$prefix}-{$yearMonth}-{$newSeq}";
    }

    // =========================================================
    // MÉTODOS DE INSTÂNCIA
    // =========================================================

    public function markAsPaid(string $method, string $reference = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_amount' => $this->total,
            'payment_method' => $method,
            'payment_reference' => $reference,
        ]);
        
        // Atualizar estatísticas da assinatura
        $this->subscription->increment('paid_invoices_count');
        $this->subscription->increment('total_paid', $this->total);
        
        // Verificar status de bloqueio do filho
        $this->filho->checkAndUpdateBlockStatus();
    }

    public function markAsOverdue(): void
    {
        $this->update([
            'status' => 'overdue',
            'overdue_at' => now(),
        ]);
        
        $this->filho->checkAndUpdateBlockStatus();
    }
}