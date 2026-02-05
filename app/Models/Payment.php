<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;
    use Auditable;

    protected $fillable = [
        'invoice_id',
        'order_id',
        'method',
        'amount',
        'change_amount',
        'status',
        'gateway_transaction_id',
        'gateway_name',
        'gateway_response',
        'card_last_digits',
        'card_brand',
        'installments',
        'pix_key',
        'pix_qrcode',
        'authorized_at',
        'confirmed_at',
        'failed_at',
        'refunded_at',
        'mp_payment_id',
        'mp_payment_intent_id',
        'mp_status',
        'mp_status_detail',
        'mp_payment_type_id',
        'mp_payment_method_id',
        'mp_issuer_id',
        'is_tef',
        'tef_device_id',
        'tef_payment_intent_id',
        'mp_response'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'installments' => 'integer',
        'gateway_response' => 'array',
        'authorized_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'mp_response' => 'array',
        'mp_request' => 'array',
    ];


    // Relacionamentos
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Métodos de negócio
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function requiresGateway(): bool
    {
        return in_array($this->method, ['pix', 'credito', 'debito']);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
