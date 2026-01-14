<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'order',
        'is_active',
        'parent_id',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
    ];


    protected static function boot()
    {
        parent::boot();

        static::updating(function ($category) {
            // 1. Evita que ela seja pai de si mesma
            if ($category->parent_id == $category->id) {
                throw new Exception("Uma categoria não pode ser subcategoria de si mesma.");
            }

            // 2. Opcional: Evita circularidade (A -> B -> A)
            if ($category->isAncestor($category->parent_id)) {
                throw new Exception("Ciclo detectado: Esta subcategoria criaria uma referência circular.");
            }
        });
    }

    // Helper para verificar se um ID já é um ancestral
    public function isAncestor($parentId)
    {
        if (empty($parentId)) {
            return false;
        }

        $parent = self::find($parentId);
        while ($parent) {
            if ($parent->id == $this->id) return true;
            $parent = $parent->parent;
        }
        return false;
    }

    protected function parentId(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => empty($value) ? null : $value,
        );
    }

    // Relacionamentos
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }



    
}