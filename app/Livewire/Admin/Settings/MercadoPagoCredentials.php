<?php

namespace App\Livewire\Admin\Settings;

use App\Models\PaymentSetting;
use App\Services\MercadoPagoService;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class MercadoPagoCredentials extends Component
{
    public PaymentSetting $config;

    // Form fields
    public string $environment = 'sandbox';
    public string $access_token = '';
    public string $public_key = '';
    
    // State
    public bool $showTokens = false;
    public bool $testing = false;
    public ?array $testResult = null;

    protected $rules = [
        'environment' => 'required|in:sandbox,production',
        'access_token' => 'required|string|min:20',
        'public_key' => 'required|string|min:20',
    ];

    public function mount()
    {
        $this->environment = $this->config->environment;
        $this->access_token = $this->config->access_token ?? '';
        $this->public_key = $this->config->public_key ?? '';
    }

    public function save()
    {
        $this->validate();

        try {
            $this->config->update([
                'environment' => $this->environment,
                'access_token' => $this->access_token,
                'public_key' => $this->public_key,
            ]);

            $this->dispatch('toast', 
                message: 'Credenciais atualizadas com sucesso!',
                type: 'success'
            );

        } catch (\Exception $e) {
            Log::error('Erro ao salvar credenciais MP', [
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('toast',
                message: 'Erro ao salvar credenciais: ' . $e->getMessage(),
                type: 'error'
            );
        }
    }

    public function testConnection()
    {
        $this->testing = true;
        $this->testResult = null;

        try {
            // Salvar antes de testar
            $this->config->update([
                'access_token' => $this->access_token,
                'public_key' => $this->public_key,
                'environment' => $this->environment,
            ]);

            $mp = app(MercadoPagoService::class);
            $result = $mp->testConnection();

            $this->testResult = $result;
            
            if ($result['success']) {
                $this->config->markAsTested(true, $result['message']);
            }

        } catch (\Exception $e) {
            $this->testResult = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } finally {
            $this->testing = false;
        }
    }

    public function toggleActive()
    {
        if (!$this->config->isConfigured()) {
            $this->dispatch('toast',
                message: 'Configure as credenciais antes de ativar!',
                type: 'error'
            );
            return;
        }

        $this->config->update([
            'is_active' => !$this->config->is_active,
        ]);

        $message = $this->config->is_active 
            ? 'Mercado Pago ativado com sucesso!' 
            : 'Mercado Pago desativado!';

        $this->dispatch('toast', 
            message: $message,
            type: 'success'
        );

        // Recarregar página para atualizar status
        $this->dispatch('refresh-page');
    }

    public function render()
    {
        return view('livewire.admin.settings.mercado-pago-credentials');
    }
}