<?php
// app/Models/Transaction.php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'filho_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'order_id',
        'compra_externa_id',
        'description',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // Relacionamentos
    public function filho(): BelongsTo
    {
        return $this->belongsTo(Filho::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function compraExterna(): BelongsTo
    {
        return $this->belongsTo(CompraExterna::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Scopes
    public function scopeCredits($query)
    {
        return $query->whereIn('type', ['credit', 'mensalidade_credit', 'refund']);
    }

    public function scopeDebits($query)
    {
        return $query->whereIn('type', ['debit', 'mensalidade_debit']);
    }
}