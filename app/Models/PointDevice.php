<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PointDevice extends Model
{
    use HasFactory;
    use HasUuid;
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'device_id',
        'device_name',
        'device_type',
        'store_id',
        'store_name',
        'pos_id',
        'location',
        'status',
        'auto_print',
        'enabled_for_pdv',
        'serial_number',
        'firmware_version',
        'capabilities',
        'last_communication_at',
        'last_payment_at',
        'last_payment_amount',
        'total_payments',
        'total_amount',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'auto_print' => 'boolean',
        'enabled_for_pdv' => 'boolean',
        'capabilities' => 'array',
        'metadata' => 'array',
        'last_communication_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'last_payment_amount' => 'decimal:2',
        'total_payments' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeEnabledForPdv($query)
    {
        return $query->where('enabled_for_pdv', true)
            ->where('status', 'active');
    }

    public function scopeByStore($query, string $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    // =========================================================
    // ACCESSORS
    // =========================================================

    /**
     * Se está online
     */
    public function getIsOnlineAttribute(): bool
    {
        if (!$this->last_communication_at) {
            return false;
        }

        // Considera online se comunicou nos últimos 5 minutos
        return $this->last_communication_at->isAfter(now()->subMinutes(5));
    }

    /**
     * Ticket médio
     */
    public function getAverageTicketAttribute(): float
    {
        if ($this->total_payments <= 0) {
            return 0;
        }

        return round($this->total_amount / $this->total_payments, 2);
    }

    // =========================================================
    // MÉTODOS DE NEGÓCIO
    // =========================================================

    /**
     * Registrar comunicação
     */
    public function recordCommunication(): void
    {
        $this->update(['last_communication_at' => now()]);
    }

    /**
     * Registrar pagamento
     */
    public function recordPayment(float $amount): void
    {
        $this->update([
            'last_payment_at' => now(),
            'last_payment_amount' => $amount,
        ]);

        $this->increment('total_payments');
        $this->increment('total_amount', $amount);
    }

    /**
     * Ativar dispositivo
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Desativar dispositivo
     */
    public function deactivate(): void
    {
        $this->update(['status' => 'inactive']);
    }

    /**
     * Marcar como offline
     */
    public function markAsOffline(): void
    {
        $this->update(['status' => 'offline']);
    }

    /**
     * Marcar como em manutenção
     */
    public function markAsMaintenance(): void
    {
        $this->update(['status' => 'maintenance']);
    }

    /**
     * Verificar se suporta funcionalidade
     */
    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    /**
     * Nome formatado para exibição
     */
    public function getDisplayName(): string
    {
        return $this->device_name ?: "{$this->device_type} ({$this->pos_id})";
    }

    /**
     * Obter device ativo por ID
     */
    public static function getActiveDevice(string $deviceId): ?self
    {
        return self::where('device_id', $deviceId)
            ->active()
            ->enabledForPdv()
            ->first();
    }
}