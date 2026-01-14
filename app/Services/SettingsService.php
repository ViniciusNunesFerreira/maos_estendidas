<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsService
{
    /**
     * Tempo de cache em segundos (1 hora)
     */
    private const CACHE_TTL = 3600;

    /**
     * Prefixo para chaves de cache
     */
    private const CACHE_PREFIX = 'settings:';

    /**
     * Obter valor de uma configuração
     * 
     * @param string $key Chave da configuração
     * @param mixed $default Valor padrão se não encontrar
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = SystemSetting::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return $setting->typed_value;
        });
    }

    /**
     * Obter todas as configurações de um grupo
     * 
     * @param string $group Nome do grupo
     * @return array
     */
    public function getByGroup(string $group): array
    {
        $cacheKey = self::CACHE_PREFIX . 'group:' . $group;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($group) {
            $settings = SystemSetting::where('group', $group)->get();

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->typed_value;
            }

            return $result;
        });
    }

    /**
     * Definir valor de uma configuração
     * 
     * @param string $key Chave da configuração
     * @param mixed $value Valor a definir
     * @param string|null $group Grupo (opcional)
     * @return SystemSetting
     */
    public function set(string $key, $value, ?string $group = null): SystemSetting
    {
        // Buscar ou criar configuração
        $setting = SystemSetting::firstOrNew(['key' => $key]);

        // Se é nova, definir grupo
        if (!$setting->exists && $group) {
            $setting->group = $group;
        }

        // Definir tipo baseado no valor
        if (!$setting->type) {
            $setting->type = $this->detectType($value);
        }

        // Definir valor
        $setting->value = $value;
        $setting->save();

        // Limpar cache
        $this->clearCache($key);
        if ($setting->group) {
            $this->clearGroupCache($setting->group);
        }

        return $setting->fresh();
    }

    /**
     * Atualizar múltiplas configurações de um grupo
     * 
     * @param string $group Nome do grupo
     * @param array $settings Array de [key => value]
     * @return bool
     */
    public function updateGroup(string $group, array $settings): bool
    {
        DB::beginTransaction();

        try {
            foreach ($settings as $key => $value) {
                $setting = SystemSetting::where('key', $key)
                    ->where('group', $group)
                    ->first();

                if ($setting && $setting->is_editable) {
                    $setting->value = $value;
                    $setting->save();
                }
            }

            DB::commit();

            // Limpar cache do grupo
            $this->clearGroupCache($group);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obter configurações de assinatura
     * 
     * @return array
     */
    public function getSubscriptionSettings(): array
    {
        return [
            'default_plan_amount' => $this->get('default_plan_amount', 350.00),
            'first_billing_days' => $this->get('first_billing_days', 30),
            'billing_cycle' => $this->get('billing_cycle', 'monthly'),
            'billing_day' => $this->get('billing_day', 5),
        ];
    }

    /**
     * Obter configurações de crédito
     * 
     * @return array
     */
    public function getCreditSettings(): array
    {
        return [
            'default_credit_limit' => $this->get('default_credit_limit', 500.00),
            'max_overdue_invoices' => $this->get('max_overdue_invoices', 3),
            'block_on_overdue' => $this->get('block_on_overdue', true),
        ];
    }

    /**
     * Obter configurações de faturamento
     * 
     * @return array
     */
    public function getBillingSettings(): array
    {
        return [
            'default_billing_close_day' => $this->get('default_billing_close_day', 30),
            'invoice_due_days' => $this->get('invoice_due_days', 10),
            'send_invoice_email' => $this->get('send_invoice_email', true),
            'send_reminder_days' => $this->get('send_reminder_days', 3),
        ];
    }

    /**
     * Obter configurações gerais
     * 
     * @return array
     */
    public function getGeneralSettings(): array
    {
        return [
            'institution_name' => $this->get('institution_name', 'Casa Lar Mãos Estendidas'),
            'institution_cnpj' => $this->get('institution_cnpj', ''),
            'institution_address' => $this->get('institution_address', ''),
            'institution_phone' => $this->get('institution_phone', ''),
            'institution_email' => $this->get('institution_email', ''),
        ];
    }

    /**
     * Obter configurações fiscais
     * 
     * @return array
     */
    public function getFiscalSettings(): array
    {
        return [
            'sat_serial' => $this->get('sat_serial', ''),
            'sat_enabled' => $this->get('sat_enabled', false),
            'emit_nfce' => $this->get('emit_nfce', false),
            'tax_regime' => $this->get('tax_regime', 'simples_nacional'),
        ];
    }

    /**
     * Resetar configuração para valor padrão
     * 
     * @param string $key
     * @return bool
     */
    public function reset(string $key): bool
    {
        $setting = SystemSetting::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        $setting->delete();
        $this->clearCache($key);

        return true;
    }

    /**
     * Resetar todas as configurações de um grupo
     * 
     * @param string $group
     * @return int Número de configurações resetadas
     */
    public function resetGroup(string $group): int
    {
        $count = SystemSetting::where('group', $group)
            ->where('is_editable', true)
            ->delete();

        $this->clearGroupCache($group);

        return $count;
    }

    /**
     * Limpar cache de uma configuração
     * 
     * @param string $key
     * @return void
     */
    private function clearCache(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    /**
     * Limpar cache de um grupo
     * 
     * @param string $group
     * @return void
     */
    private function clearGroupCache(string $group): void
    {
        Cache::forget(self::CACHE_PREFIX . 'group:' . $group);

        // Limpar também cache individual de cada configuração do grupo
        $settings = SystemSetting::where('group', $group)->get();
        foreach ($settings as $setting) {
            $this->clearCache($setting->key);
        }
    }

    /**
     * Detectar tipo do valor
     * 
     * @param mixed $value
     * @return string
     */
    private function detectType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_float($value)) {
            return 'decimal';
        }

        if (is_array($value)) {
            return 'array';
        }

        return 'string';
    }

    /**
     * Verificar se configuração existe
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return SystemSetting::where('key', $key)->exists();
    }

    /**
     * Obter todas as configurações
     * 
     * @return array Agrupado por grupo
     */
    public function all(): array
    {
        $settings = SystemSetting::orderBy('group')->orderBy('key')->get();

        $result = [];
        foreach ($settings as $setting) {
            if (!isset($result[$setting->group])) {
                $result[$setting->group] = [];
            }

            $result[$setting->group][$setting->key] = [
                'value' => $setting->typed_value,
                'type' => $setting->type,
                'description' => $setting->description,
                'is_editable' => $setting->is_editable,
            ];
        }

        return $result;
    }

    /**
     * Exportar configurações para array
     * 
     * @return array
     */
    public function export(): array
    {
        $settings = SystemSetting::all();

        $result = [];
        foreach ($settings as $setting) {
            $result[] = [
                'group' => $setting->group,
                'key' => $setting->key,
                'value' => $setting->value,
                'type' => $setting->type,
                'description' => $setting->description,
            ];
        }

        return $result;
    }

    /**
     * Importar configurações de array
     * 
     * @param array $settings
     * @return int Número de configurações importadas
     */
    public function import(array $settings): int
    {
        $count = 0;

        DB::beginTransaction();

        try {
            foreach ($settings as $data) {
                SystemSetting::updateOrCreate(
                    ['key' => $data['key']],
                    [
                        'group' => $data['group'],
                        'value' => $data['value'],
                        'type' => $data['type'] ?? 'string',
                        'description' => $data['description'] ?? null,
                    ]
                );
                $count++;
            }

            DB::commit();

            // Limpar todo o cache
            Cache::tags([self::CACHE_PREFIX])->flush();

            return $count;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}