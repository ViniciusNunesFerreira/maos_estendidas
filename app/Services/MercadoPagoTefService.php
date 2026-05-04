<?php

namespace App\Services;

use App\Models\PaymentSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MercadoPagoTefService
{
    private $accessToken;

    public function __construct()
    {
        // Garante que pega a chave correta da tabela de configurações
        $settings = PaymentSetting::first();
        $this->accessToken = $settings ? $settings->mp_access_token : config('services.mercadopago.access_token');
    }

    /**
     * Aciona a maquininha Point TEF online e injeta o valor na tela.
     */
    public function createIntent(string $tefDeviceId, float $amount, string $paymentMethod): ?string
    {
        if (!$this->accessToken) {
            throw new Exception("Access Token do Mercado Pago não configurado.");
        }

        $methodType = $paymentMethod === 'debit_card' ? 'debit_card' : 'credit_card';

        $response = Http::withToken($this->accessToken)
            ->post("https://api.mercadopago.com/point/integration-api/devices/{$tefDeviceId}/payment-intents", [
                'amount' => (int) ($amount * 100), // MP Point Integration API exige centavos
                'payment' => [
                    'type' => $methodType,
                    'installments' => 1,
                ],
                'additional_info' => [
                    'external_reference' => uniqid('kiosk_'),
                    'print_on_terminal' => true
                ]
            ]);

        if ($response->successful()) {
            return $response->json('id');
        }

        // TRATAMENTO KIOSK-FIRST: Maquininha morta, descarregada ou sem rede.
        if ($response->status() === 404 || str_contains(strtolower($response->body()), 'offline')) {
             Log::critical("ALERTA KIOSK: Terminal Point ID {$tefDeviceId} offline ou desvinculado.");
             throw new Exception("A maquininha de cartão está desligada ou sem internet. Por favor, utilize o PIX.");
        }

        Log::error("Erro Mercado Pago TEF Create: " . $response->body());
        throw new Exception('O serviço de cartão está temporariamente instável. Tente usar o PIX.');
    }

    /**
     * Verifica o que o cliente fez na maquininha (Poling Seguro)
     */
    public function getPaymentIntentStatus(string $intentId): string
    {
        $response = Http::withToken($this->accessToken)
            ->get("https://api.mercadopago.com/point/integration-api/payment-intents/{$intentId}");

        if ($response->successful()) {
            return $response->json('state'); // OPEN, FINISHED, CANCELED, ERROR
        }

        Log::warning("TEF Status Sync Warning: Não foi possível obter status do intent {$intentId}");
        return 'OPEN'; // Assume que ainda está aberto em caso de falha de rede rápida
    }

    /**
     * Remove o valor da tela da maquininha se o cliente desistir no Totem
     */
    public function cancelIntent(string $tefDeviceId, string $intentId): bool
    {
        $response = Http::withToken($this->accessToken)
            ->delete("https://api.mercadopago.com/point/integration-api/devices/{$tefDeviceId}/payment-intents/{$intentId}");

        if (!$response->successful()) {
            Log::warning("Falha ao abortar transação na máquina: " . $response->body());
        }

        return $response->successful();
    }
}