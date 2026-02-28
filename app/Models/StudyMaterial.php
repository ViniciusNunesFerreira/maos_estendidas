<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Traits\HasUuid;

class StudyMaterial extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;


    protected $fillable = [
        'created_by',
        'category_id',
        'title',
        'slug',
        'description',
        'content',
        'type',
        'category',
        'file_path',
        'file_name',
        'file_size',
        'thumbnail_path',
        'is_published',
        'is_free',
        'price',
        'download_count',
        'view_count',
        'tags',
        'published_at',
        'external_video_id',
        'processing_status'
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_published' => 'boolean',
            'is_free' => 'boolean',
            'price' => 'decimal:2',
            'tags' => 'array',
            'download_count' => 'integer',
            'view_count' => 'integer',
            'file_size' => 'integer',
        ];
    }

    protected $appends = [
        'file_url',
        'thumbnail_url',
    ];

    // =========================================================
    // RELACIONAMENTOS
    // =========================================================

    
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(MaterialDownload::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(MaterialView::class);
    }

     // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    public function scopePaid($query)
    {
        return $query->where('is_free', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

  
    public function scopeWithTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopePopular($query)
    {
        return $query->orderBy('download_count', 'desc')
                    ->orderBy('view_count', 'desc');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

     // Métodos auxiliares
    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'ebook' => 'E-book/PDF',
            'video' => 'Vídeo',
            'article' => 'Artigo',
            default => 'Outro'
        };
    }


    protected function fileUrl(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => 
               ($attributes['file_path'] ?? null)
                    ? Storage::disk('bunnycdn')->url($attributes['file_path']) 
                    : null,
        );
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => 
                ($attributes['thumbnail_path'] ?? null)
                    ? Storage::disk('bunnycdn')->url($attributes['thumbnail_path']) 
                    : null,
        );
    }


    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) {
            return '';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isEbook(): bool
    {
        return $this->type === 'ebook';
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

   
    public function canBeDownloaded(): bool
    {
        return $this->is_published && $this->file_path;
    }


}
