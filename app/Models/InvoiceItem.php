<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'invoice_id',
        'order_id',
        'order_item_id',
        'product_id',
        'purchase_date',
        'description',
        'category',
        'location',
        'quantity',
        'unit_price',
        'subtotal',
        'discount_amount',
        'total',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // =========================================================
    // RELACIONAMENTOS
    // =========================================================

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // =========================================================
    // MÉTODOS AUXILIARES
    // =========================================================

    public function isFromLoja(): bool
    {
        return $this->location === 'loja';
    }

    public function isFromCantina(): bool
    {
        return $this->location === 'cantina';
    }
}