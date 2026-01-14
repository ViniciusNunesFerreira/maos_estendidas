<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;
    use Auditable;

    protected $fillable = [
        'filho_id',
        'approved_by_user_id',
        'plan_name',
        'plan_description',
        'amount',
        'billing_cycle',
        'billing_day',
        'started_at',
        'first_billing_date',
        'next_billing_date',
        'cancelled_at',
        'paused_at',
        'ends_at',
        'status',
        'status_reason',
        'invoices_count',
        'paid_invoices_count',
        'total_paid',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'billing_day' => 'integer',
        'started_at' => 'date',
        'first_billing_date' => 'date',
        'next_billing_date' => 'date',
        'ends_at' => 'date',
        'cancelled_at' => 'datetime',
        'paused_at' => 'datetime',
        'total_paid' => 'decimal:2',
        'invoices_count' => 'integer',
        'paid_invoices_count' => 'integer',
    ];

    protected $attributes = [
        'status' => 'pending',
        'billing_cycle' => 'monthly',
        'billing_day' => 10,
        'invoices_count' => 0,
        'paid_invoices_count' => 0,
        'total_paid' => 0,
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    // =========================================================
    // RELACIONAMENTOS
    // =========================================================

    public function filho(): BelongsTo
    {
        return $this->belongsTo(Filho::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->where('type', 'subscription');
    }

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeDueForBilling($query)
    {
        return $query->where('status', 'active')
            ->where('next_billing_date', '<=', today());
    }

    // =========================================================
    // MÉTODOS
    // =========================================================

    public function calcularDatasFaturamento(int $billingDay = 28)
    {
        $hoje = Carbon::today();
        
        // 1. Definir o faturamento deste mês
        $dataFechamentoDesteMes = Carbon::today()->day($billingDay);

        // 2. Se hoje for DEPOIS do dia 28, o primeiro faturamento é no mês que vem
        if ($hoje->gt($dataFechamentoDesteMes)) {
            $firstBillingDate = $dataFechamentoDesteMes->copy()->addMonth();
        } else {
            $firstBillingDate = $dataFechamentoDesteMes->copy();
        }

        // 3. O próximo é sempre um mês após o primeiro
        $nextBillingDate = $firstBillingDate->copy()->addMonth();

        return [
            'first_billing_date' => $firstBillingDate->toDateString(), 
            'next_billing_date'  => $nextBillingDate->toDateString(), 
        ];
    }

    

    public function pause(string $reason = null): void
    {
        $this->update([
            'status' => 'paused',
            'paused_at' => now(),
            'status_reason' => $reason,
        ]);
    }

    public function reactivate(): void
    {
        $this->update([
            'status' => 'active',
            'paused_at' => null,
            'status_reason' => null,
        ]);
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'status_reason' => $reason,
        ]);
    }

    public function suspend(): void
    {
        $this->update([
            'status' => 'suspended',
            'status_reason' => 'Suspenso por inadimplência',
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}