<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncQueue extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = 'sync_queue';

    protected $fillable = [
        'device_id',
        'entity_type',
        'entity_id',
        'operation',
        'payload',
        'status',
        'attempts',
        'max_attempts',
        'error_message',
        'processed_at',
        'priority',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'priority' => 'integer',
        'processed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'attempts' => 0,
        'max_attempts' => 3,
        'priority' => 0,
    ];

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                     ->whereColumn('attempts', '<', 'max_attempts');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeForEntity($query, string $entityType, string $entityId = null)
    {
        $query->where('entity_type', $entityType);
        
        if ($entityId) {
            $query->where('entity_id', $entityId);
        }
        
        return $query;
    }

    public function scopeByPriority($query)
    {
        return $query->orderByDesc('priority')->orderBy('created_at');
    }

    public function scopeRetryable($query)
    {
        return $query->where('status', 'failed')
                     ->whereColumn('attempts', '<', 'max_attempts');
    }

    // =========================================================
    // MÉTODOS
    // =========================================================

    public function isPending(): bool
    {
        return $this->status === 'pending' && $this->attempts < $this->max_attempts;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->attempts < $this->max_attempts;
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->increment('attempts');
        
        $status = $this->attempts >= $this->max_attempts ? 'failed' : 'pending';
        
        $this->update([
            'status' => $status,
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }

    public function retry(): void
    {
        if ($this->canRetry()) {
            $this->update([
                'status' => 'pending',
                'error_message' => null,
            ]);
        }
    }

    public function getOperationDescription(): string
    {
        return match($this->operation) {
            'create' => 'Criar',
            'update' => 'Atualizar',
            'delete' => 'Deletar',
            'sync' => 'Sincronizar',
            default => 'Operação Desconhecida',
        };
    }

    public static function enqueue(
        string $deviceId,
        string $entityType,
        string $entityId,
        string $operation,
        array $payload,
        int $priority = 0
    ): self {
        return static::create([
            'device_id' => $deviceId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'operation' => $operation,
            'payload' => $payload,
            'priority' => $priority,
        ]);
    }
}