<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'system_settings';

    /**
     * Campos conforme migration real
     */
    protected $fillable = [
        'group',        // string - Grupo da configuração
        'key',          // string - Chave única
        'value',        // json - Valor (flexível)
        'type',         // enum - Tipo do valor
        'description',  // string - Descrição
        'is_editable',  // boolean - Se pode ser editado
    ];

    /**
     * Casts conforme migration
     */
    protected $casts = [
        'value' => 'array',         // JSON para array
        'is_editable' => 'boolean',
    ];

    /**
     * Tipos válidos (conforme enum na migration)
     */
    public const TYPES = [
        'string',
        'integer',
        'decimal',
        'boolean',
        'array',
        'json',
    ];

    /**
     * Grupos de configuração
     */
    public const GROUPS = [
        'general' => 'Configurações Gerais',
        'subscription' => 'Assinaturas',
        'credit' => 'Crédito',
        'billing' => 'Faturamento',
        'fiscal' => 'Fiscal',
        'notifications' => 'Notificações',
        'sms' => 'SMS',
    ];

    /**
     * Scopes
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    public function scopeEditable($query)
    {
        return $query->where('is_editable', true);
    }

    /**
     * Accessors - Retorna o valor no tipo correto
     */
    public function getTypedValueAttribute()
    {
        $value = $this->value;

        // Se value é array (do cast JSON), pegar primeiro elemento
        if (is_array($value) && count($value) === 1) {
            $value = $value[0];
        }

        return match($this->type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => (bool) $value,
            'array', 'json' => is_array($value) ? $value : json_decode($value, true),
            default => (string) $value,
        };
    }

    /**
     * Mutators - Garante que valor é salvo como JSON
     */
    public function setValueAttribute($value)
    {
        // Se já é string JSON, usar direto
        if (is_string($value) && $this->isJson($value)) {
            $this->attributes['value'] = $value;
            return;
        }

        // Converter para JSON
        $this->attributes['value'] = json_encode($value);
    }

    /**
     * Helper - Verifica se string é JSON válido
     */
    private function isJson($string): bool
    {
        if (!is_string($string)) {
            return false;
        }

        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}