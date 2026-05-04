<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controller para gerenciar configurações GLOBAIS de pagamento.
 * 
 */
class PaymentSettingsController extends Controller
{
    protected ?MercadoPagoService $mercadoPago = null;

    public function __construct()
    {
        // Tentar instanciar MercadoPago, mas NÃO falhar se não configurado
        try {
            $service = app(MercadoPagoService::class);

            if ($service->isConfigured()) {
                $this->mercadoPago = $service;
            }
        } catch (\Exception $e) {
           
            \Log::debug('MercadoPagoService não disponível no PaymentSettingsController', [
                'error' => $e->getMessage(),
            ]);
        }
    }

   
    public function index(): View
    {
        $config = PaymentSetting::getMercadoPagoConfig();
        
        if (!$config) {
            $config = PaymentSetting::create([
                'gateway' => 'mercadopago',
                'environment' => 'sandbox',
                'is_active' => false,
            ]);
        }


        return view('admin.settings.payment-gateways', [
            'config' => $config,
            'title' => 'Configurações de Pagamento',
        ]);
    }

    /**
     * Salvar credenciais do Mercado Pago
     * 
     */
    public function updateCredentials(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'environment' => 'required|in:sandbox,production',
            'access_token' => 'required|string',
            'public_key' => 'required|string',
            'webhook_secret' => 'nullable|string',
        ]);

        $config = PaymentSetting::getMercadoPagoConfig();

        $config->update([
            'environment' => $validated['environment'],
            'access_token' => $validated['access_token'],
            'public_key' => $validated['public_key'],
            'webhook_secret' => $validated['webhook_secret'],
        ]);

        return redirect()
            ->route('admin.settings.payment-gateways')
            ->with('success', 'Credenciais do Mercado Pago atualizadas com sucesso!');
    }

    /**
     * Salvar configurações Point (Globais)
     * 
     */
    public function updatePoint(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'device_id' => 'nullable|string',
            'store_id' => 'nullable|string',
            'pos_id' => 'nullable|string',
            'auto_print_receipt' => 'boolean',
        ]);

        $config = PaymentSetting::getMercadoPagoConfig();

        $config->update($validated);

        return redirect()
            ->route('admin.settings.payment-gateways')
            ->with('success', 'Configurações do Point atualizadas!');
    }

    /**
     * Ativar/desativar métodos de pagamento
     * 
     */
    public function updateMethods(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'methods' => 'array',
            'methods.*' => 'in:pix,credit_card,debit_card,tef',
        ]);

        $config = PaymentSetting::getMercadoPagoConfig();

        $config->update([
            'active_methods' => $validated['methods'] ?? [],
        ]);

        return redirect()
            ->route('admin.settings.payment-gateways')
            ->with('success', 'Métodos de pagamento atualizados!');
    }

    /**
     * Ativar/desativar Mercado Pago
     */
    public function toggle(Request $request): RedirectResponse
    {
        $config = PaymentSetting::getMercadoPagoConfig();

        if (!$config->isConfigured()) {
            return redirect()
                ->route('admin.settings.payment-gateways')
                ->with('error', 'Configure as credenciais antes de ativar!');
        }

        $config->update([
            'is_active' => !$config->is_active,
        ]);

        $message = $config->is_active 
            ? 'Mercado Pago ativado com sucesso!' 
            : 'Mercado Pago desativado!';

        return redirect()
            ->route('admin.settings.payment-gateways')
            ->with('success', $message);
    }

    /**
     * Testar conexão GLOBAL com a API do Mercado Pago
     * 
     */
    public function testConnection(): RedirectResponse
    {
        try {
            // Recarregar service (pode ter sido configurado agora)
            if (!$this->mercadoPago) {
                try {
                    $service = app(MercadoPagoService::class);
                    if ($service->isConfigured()) {
                        $this->mercadoPago = $service;
                    }
                } catch (\Exception $e) {
                    // Ainda não configurado
                }
            }

            if (!$this->mercadoPago) {
                return redirect()
                    ->route('admin.settings.payment-gateways')
                    ->with('error', 'Configure as credenciais antes de testar!');
            }

            $result = $this->mercadoPago->testConnection();

            if ($result['success']) {
                $config = PaymentSetting::getMercadoPagoConfig();
                $config->markAsTested(true, $result['message']);

                return redirect()
                    ->route('admin.settings.payment-gateways')
                    ->with('success', $result['message']);
            } else {
                return redirect()
                    ->route('admin.settings.payment-gateways')
                    ->with('error', $result['message']);
            }

        } catch (\Exception $e) {
            return redirect()
                ->route('admin.settings.payment-gateways')
                ->with('error', 'Erro ao testar: ' . $e->getMessage());
        }
    }
}