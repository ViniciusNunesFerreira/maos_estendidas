<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use App\Models\PointDevice;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

/**
 * Controller para gerenciar configurações de pagamento
 * 
 * CORREÇÃO: Não falhar no construtor se MP não estiver configurado
 */
class PaymentSettingsController extends Controller
{
    protected ?MercadoPagoService $mercadoPago = null;

    public function __construct()
    {
        // Tentar instanciar MercadoPago, mas NÃO falhar se não configurado
        // Isso permite acessar a página de configuração mesmo sem config
        try {
            $service = app(MercadoPagoService::class);
            
            // Verificar se está realmente configurado
            if ($service->isConfigured()) {
                $this->mercadoPago = $service;
            }
        } catch (\Exception $e) {
            // Silenciosamente ignorar - usuário precisa configurar
            \Log::debug('MercadoPagoService não disponível no PaymentSettingsController', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Exibir configurações de pagamento
     * 
     * GET /admin/settings/payment-gateways
     */
    public function index(): View
    {
        $config = PaymentSetting::getMercadoPagoConfig();
        
        if (!$config) {
            // Criar configuração padrão se não existir
            $config = PaymentSetting::create([
                'gateway' => 'mercadopago',
                'environment' => 'sandbox',
                'is_active' => false,
            ]);
        }

       

        $devices = PointDevice::orderBy('device_name')->get();

        return view('admin.settings.payment-gateways', [
            'config' => $config,
            'devices' => $devices,
            'title' => 'Configurações de Pagamento',
        ]);
    }

    /**
     * Salvar credenciais do Mercado Pago
     * 
     * POST /admin/settings/payment-gateways/credentials
     */
    public function updateCredentials(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'environment' => 'required|in:sandbox,production',
            'access_token' => 'required|string',
            'public_key' => 'required|string',
        ]);

        $config = PaymentSetting::getMercadoPagoConfig();

        $config->update([
            'environment' => $validated['environment'],
            'access_token' => $validated['access_token'],
            'public_key' => $validated['public_key'],
        ]);

        return redirect()
            ->route('admin.settings.payment-gateways')
            ->with('success', 'Credenciais do Mercado Pago atualizadas com sucesso!');
    }

    /**
     * Salvar configurações Point
     * 
     * POST /admin/settings/payment-gateways/point
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
     * POST /admin/settings/payment-gateways/methods
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
     * 
     * POST /admin/settings/payment-gateways/toggle
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
     * Testar conexão com Mercado Pago
     * 
     * POST /admin/settings/payment-gateways/test
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

    /**
     * Cadastrar novo device Point
     * 
     * POST /admin/settings/payment-gateways/devices
     */
    public function storeDevice(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|string|unique:point_devices,device_id',
            'device_name' => 'required|string|max:255',
            'location' => 'nullable|string',
            'auto_print' => 'boolean',
        ]);

        PointDevice::create([
            'device_id' => $validated['device_id'],
            'device_name' => $validated['device_name'],
            'location' => $validated['location'] ?? null,
            'auto_print' => $validated['auto_print'] ?? false,
            'status' => 'active',
            'enabled_for_pdv' => true,
        ]);

        return redirect()
            ->route('admin.settings.payment-gateways')
            ->with('success', 'Maquininha cadastrada com sucesso!');
    }

    /**
     * Atualizar device Point
     * 
     * PUT /admin/settings/payment-gateways/devices/{device}
     */
    public function updateDevice(Request $request, PointDevice $device): RedirectResponse
    {
        $validated = $request->validate([
            'device_name' => 'required|string|max:255',
            'location' => 'nullable|string',
            'auto_print' => 'boolean',
            'enabled_for_pdv' => 'boolean',
        ]);

        $device->update($validated);

        return redirect()
            ->route('admin.settings.payment-gateways')
            ->with('success', 'Maquininha atualizada!');
    }

    /**
     * Ativar/desativar device
     * 
     * POST /admin/settings/payment-gateways/devices/{device}/toggle
     */
    public function toggleDevice(PointDevice $device): RedirectResponse
    {
        if ($device->status === 'active') {
            $device->deactivate();
            $message = 'Maquininha desativada!';
        } else {
            $device->activate();
            $message = 'Maquininha ativada!';
        }

        return redirect()
            ->route('admin.settings.payment-gateways')
            ->with('success', $message);
    }

    /**
     * Excluir device
     * 
     * DELETE /admin/settings/payment-gateways/devices/{device}
     */
    public function destroyDevice(PointDevice $device): RedirectResponse
    {
        $device->delete();

        return redirect()
            ->route('admin.settings.payment-gateways')
            ->with('success', 'Maquininha removida!');
    }
}