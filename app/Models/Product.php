<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;
    use Auditable;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'sku',
        'barcode',
        'image_url',
        'price',
        'cost_price',
        'stock_quantity',
        'min_stock_alert',
        'track_stock',
        'type', // 'loja' ou 'cantina'
        'requires_preparation',
        'preparation_time_minutes',
        'preparation_station',
        'is_active',
        'available_pdv',
        'available_totem',
        'available_app',
        'ncm',
        'cest',
        'icms_rate',
        'pis_rate',
        'cofins_rate',
        'tags',
        'allergens',
        'calories',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock_alert' => 'integer',
        'track_stock' => 'boolean',
        'requires_preparation' => 'boolean',
        'preparation_time_minutes' => 'integer',
        'is_active' => 'boolean',
        'available_pdv' => 'boolean',
        'available_totem' => 'boolean',
        'available_app' => 'boolean',
        'icms_rate' => 'decimal:2',
        'pis_rate' => 'decimal:2',
        'cofins_rate' => 'decimal:2',
        'tags' => 'array',
        'allergens' => 'array',
        'calories' => 'integer',
    ];

    protected $appends = ['is_low_stock'];

    // =========================================================
    // RELACIONAMENTOS
    // =========================================================

    /**
     * Categoria do produto
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Itens de pedido relacionados
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Movimentações de estoque
     * 
     * @return HasMany
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // =========================================================
    // ACCESSORS
    // =========================================================

    /**
     * Verifica se está com estoque baixo
     */
    public function getIsLowStockAttribute(): bool
    {
        if (!$this->track_stock) {
            return false;
        }

        return $this->stock_quantity <= $this->min_stock_alert;
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // Se o valor for nulo ou o arquivo não existir, retorna um placeholder
                if (!$value || !Storage::disk('public')->exists($value)) {
                    return asset('assets/images/no-image.png');
                }
                
                return asset('storage/' . $value);
            },
        );
    }

    // =========================================================
    // SCOPES
    // =========================================================

    /**
     * Produtos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Produtos disponíveis para origem específica
     */
    public function scopeAvailableFor($query, string $origin)
    {
        $field = "available_{$origin}";
        return $query->where('is_active', true)
                    ->where($field, true);
    }

    /**
     * Produtos com estoque baixo
     */
    public function scopeLowStock($query)
    {
        return $query->where('track_stock', true)
                    ->whereColumn('stock_quantity', '<=', 'min_stock_alert');
    }

    /**
     * Produtos sem estoque
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('track_stock', true)
                    ->where('stock_quantity', '<=', 0);
    }

    /**
     * Busca por nome, SKU ou código de barras
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'ILIKE', "%{$term}%")
              ->orWhere('description', 'ILIKE', "%{$term}%")
              ->orWhere('sku', 'ILIKE', "%{$term}%")
              ->orWhere('barcode', 'ILIKE', "%{$term}%");
        });
    }

    /**
     * Produtos da loja
     */
    public function scopeLoja($query)
    {
        return $query->where('type', 'loja');
    }

    /**
     * Produtos da cantina
     */
    public function scopeCantina($query)
    {
        return $query->where('type', 'cantina');
    }

    // =========================================================
    // MÉTODOS DE NEGÓCIO
    // =========================================================

    /**
     * Verifica se tem estoque disponível
     */
    public function hasStock(int $quantity = 1): bool
    {
        if (!$this->track_stock) {
            return true;
        }

        return $this->stock_quantity >= $quantity;
    }

    /**
     * Decrementa o estoque
     */
    public function decrementStock(int $quantity): void
    {
        if ($this->track_stock) {
            $this->decrement('stock_quantity', $quantity);
        }
    }

    /**
     * Incrementa o estoque
     */
    public function incrementStock(int $quantity): void
    {
        if ($this->track_stock) {
            $this->increment('stock_quantity', $quantity);
        }
    }

    /**
     * Verifica se está com estoque baixo
     */
    public function isLowStock(): bool
    {
        if (!$this->track_stock) {
            return false;
        }

        return $this->stock_quantity <= $this->min_stock_alert;
    }

    /**
     * Verifica se está sem estoque
     */
    public function isOutOfStock(): bool
    {
        if (!$this->track_stock) {
            return false;
        }

        return $this->stock_quantity <= 0;
    }

    /**
     * Retorna a porcentagem de estoque em relação ao mínimo
     */
    public function getStockPercentage(): float
    {
        if (!$this->track_stock || $this->min_stock_alert <= 0) {
            return 100;
        }

        return min(100, ($this->stock_quantity / $this->min_stock_alert) * 100);
    }

    /**
     * Retorna severity do estoque (para alertas)
     */
    public function getStockSeverity(): string
    {
        if (!$this->track_stock) {
            return 'none';
        }

        $percentage = $this->getStockPercentage();

        if ($percentage <= 0) {
            return 'critical'; // Esgotado
        } elseif ($percentage <= 25) {
            return 'high'; // Crítico
        } elseif ($percentage <= 50) {
            return 'medium'; // Médio
        } elseif ($percentage <= 100) {
            return 'low'; // Baixo mas ok
        }

        return 'ok'; // Acima do mínimo
    }

    /**
     * Retorna cor para o estoque (Tailwind)
     */
    public function getStockColor(): string
    {
        return match($this->getStockSeverity()) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'blue',
            'ok' => 'green',
            default => 'gray',
        };
    }

    /**
     * Flag temporária para pular ProductObserver
     * Usado quando StockService já criou o movimento
     */
    public function skipStockObserver(): self
    {
        $this->_skip_stock_observer = true;
        return $this;
    }
}