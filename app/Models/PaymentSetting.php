<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PaymentSetting extends Model
{
    use HasFactory;
    use HasUuid;
    use Auditable;

    protected $fillable = [
        'gateway',
        'environment',
        'access_token',
        'public_key',
        'client_id',
        'client_secret',
        'device_id',
        'store_id',
        'pos_id',
        'auto_print_receipt',
        'active_methods',
        'webhook_url',
        'webhook_secret',
        'is_active',
        'tested_at',
        'last_test_result',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'active_methods' => 'array',
        'metadata' => 'array',
        'auto_print_receipt' => 'boolean',
        'is_active' => 'boolean',
        'tested_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'client_secret',
        'webhook_secret',
    ];

    // =========================================================
    // ACCESSORS & MUTATORS
    // =========================================================

    /**
     * Criptografar access token ao salvar
     */
    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Descriptografar access token ao ler
     */
    public function getAccessTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Criptografar client secret ao salvar
     */
    public function setClientSecretAttribute($value): void
    {
        $this->attributes['client_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Descriptografar client secret ao ler
     */
    public function getClientSecretAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    // =========================================================
    // SCOPES
    // =========================================================

    /**
     * Configuração ativa
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Por gateway
     */
    public function scopeGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Ambiente de produção
     */
    public function scopeProduction($query)
    {
        return $query->where('environment', 'production');
    }

    /**
     * Ambiente de sandbox
     */
    public function scopeSandbox($query)
    {
        return $query->where('environment', 'sandbox');
    }

    // =========================================================
    // MÉTODOS DE NEGÓCIO
    // =========================================================

    /**
     * Verificar se método está ativo
     */
    public function hasActiveMethod(string $method): bool
    {
        return in_array($method, $this->active_methods ?? []);
    }

    /**
     * Adicionar método ativo
     */
    public function enableMethod(string $method): void
    {
        $methods = $this->active_methods ?? [];
        
        if (!in_array($method, $methods)) {
            $methods[] = $method;
            $this->update(['active_methods' => $methods]);
        }
    }

    /**
     * Remover método ativo
     */
    public function disableMethod(string $method): void
    {
        $methods = $this->active_methods ?? [];
        
        if (($key = array_search($method, $methods)) !== false) {
            unset($methods[$key]);
            $this->update(['active_methods' => array_values($methods)]);
        }
    }

    /**
     * Verificar se está em produção
     */
    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    /**
     * Verificar se está em sandbox
     */
    public function isSandbox(): bool
    {
        return $this->environment === 'sandbox';
    }

    /**
     * Marcar como testado
     */
    public function markAsTested(bool $success, ?string $message = null): void
    {
        $this->update([
            'tested_at' => now(),
            'last_test_result' => json_encode([
                'success' => $success,
                'message' => $message,
                'tested_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Obter URL base da API do Mercado Pago
     */
    public function getApiBaseUrl(): string
    {
        return $this->isProduction()
            ? 'https://api.mercadopago.com'
            : 'https://api.mercadopago.com'; // Mesmo endpoint, diferença é nas credenciais
    }

    /**
     * Obter configuração do Mercado Pago ativa
     */
    public static function getMercadoPagoConfig(): ?self
    {
        return self::active()
            ->gateway('mercadopago')
            ->first();
    }

    /**
     * Validar se configuração está completa
     */
    public function isConfigured(): bool
    {
        return !empty($this->access_token) && !empty($this->public_key);
    }

    /**
     * Validar se Point está configurado
     */
    public function isPointConfigured(): bool
    {
        return $this->isConfigured() && !empty($this->device_id);
    }
}