<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockMovement extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;
    use Auditable;

    protected $fillable = [
        'product_id',
        'user_id',
        'order_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'total_cost',
        'reference',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Tipos válidos de movimentação
     */
    public const TYPE_ENTRY = 'entry';
    public const TYPE_OUT = 'out';
    public const TYPE_SALE = 'sale';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_RETURN = 'return';
    public const TYPE_LOSS = 'loss';
    public const TYPE_TRANSFER = 'transfer';

    // =========================================================
    // RELACIONAMENTOS
    // =========================================================

    /**
     * Produto relacionado
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Usuário que registrou a movimentação
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pedido relacionado (quando for venda)
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // =========================================================
    // SCOPES
    // =========================================================

    /**
     * Scope para filtrar por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para entradas
     */
    public function scopeEntries($query)
    {
        return $query->where('type', self::TYPE_ENTRY);
    }

    /**
     * Scope para saídas
     */
    public function scopeOuts($query)
    {
        return $query->whereIn('type', [self::TYPE_OUT, self::TYPE_SALE, self::TYPE_LOSS]);
    }

    /**
     * Scope para ajustes
     */
    public function scopeAdjustments($query)
    {
        return $query->where('type', self::TYPE_ADJUSTMENT);
    }

    /**
     * Scope para um produto específico
     */
    public function scopeForProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope para um período
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // =========================================================
    // MÉTODOS AUXILIARES
    // =========================================================

    /**
     * Verifica se é uma entrada de estoque
     */
    public function isEntry(): bool
    {
        return $this->type === self::TYPE_ENTRY;
    }

    /**
     * Verifica se é uma saída de estoque
     */
    public function isOut(): bool
    {
        return in_array($this->type, [self::TYPE_OUT, self::TYPE_SALE, self::TYPE_LOSS]);
    }

    /**
     * Verifica se é um ajuste
     */
    public function isAdjustment(): bool
    {
        return $this->type === self::TYPE_ADJUSTMENT;
    }

    /**
     * Verifica se é uma venda
     */
    public function isSale(): bool
    {
        return $this->type === self::TYPE_SALE;
    }

    /**
     * Retorna a descrição formatada do tipo
     */
    public function getTypeDescription(): string
    {
        return match($this->type) {
            self::TYPE_ENTRY => 'Entrada de Estoque',
            self::TYPE_OUT => 'Saída Manual',
            self::TYPE_SALE => 'Venda',
            self::TYPE_ADJUSTMENT => 'Ajuste de Inventário',
            self::TYPE_RETURN => 'Devolução',
            self::TYPE_LOSS => 'Perda',
            self::TYPE_TRANSFER => 'Transferência',
            default => 'Desconhecido',
        };
    }

    /**
     * Retorna o ícone adequado para o tipo
     */
    public function getTypeIcon(): string
    {
        return match($this->type) {
            self::TYPE_ENTRY => '📦',
            self::TYPE_OUT => '📤',
            self::TYPE_SALE => '🛒',
            self::TYPE_ADJUSTMENT => '⚖️',
            self::TYPE_RETURN => '↩️',
            self::TYPE_LOSS => '❌',
            self::TYPE_TRANSFER => '🔄',
            default => '❓',
        };
    }

    /**
     * Retorna a cor para o tipo (Tailwind)
     */
    public function getTypeColor(): string
    {
        return match($this->type) {
            self::TYPE_ENTRY => 'green',
            self::TYPE_OUT, self::TYPE_LOSS => 'red',
            self::TYPE_SALE => 'blue',
            self::TYPE_ADJUSTMENT => 'yellow',
            self::TYPE_RETURN => 'purple',
            self::TYPE_TRANSFER => 'indigo',
            default => 'gray',
        };
    }
}