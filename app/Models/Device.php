<?php

namespace App\Models;


use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'internal_id',
        'type',
        'tef_provider',
        'tef_device_id',
        'ip_address',
        'mac_address',
        'last_ping_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_ping_at' => 'datetime',
    ];

    /**
     * Escopo para pegar apenas terminais de autoatendimento ativos
     */
    public function scopeActiveKiosks($query)
    {
        return $query->where('type', 'kiosk')->where('is_active', true);
    }
}
