<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\HasUuid;

class MaterialView extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'user_id',
        'study_material_id',
        'ip_address',
        'user_agent',
        'duration_seconds',
        'viewed_at'
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
        ];
    }

    // Relacionamentos
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studyMaterial(): BelongsTo
    {
        return $this->belongsTo(StudyMaterial::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByMaterial($query, $materialId)
    {
        return $query->where('study_material_id', $materialId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Métodos auxiliares
    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_seconds) {
            return '0s';
        }

        $hours = intval($this->duration_seconds / 3600);
        $minutes = intval(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        }

        if ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        }

        return sprintf('%ds', $seconds);
    }
}
