<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentSetting;
use Exception;

class MercadoPagoTefService
{
    /**
     * Resgata o Access Token ativo no banco de dados.
     * Caso não encontre, tenta o fallback pelo .env/config.
     */
    private function getAccessToken(): ?string
    {
        $settings = PaymentSetting::getMercadoPagoConfig();
        return $settings ? $settings->access_token : config('services.mercadopago.access_token');
    }

    /**
     * Aciona a maquininha Point TEF online e injeta o valor na tela.
     */
    public function createIntent(string $tefDeviceId, float $amount, string $paymentMethod): ?string
    {
        $token = $this->getAccessToken();
        if (!$token) {
            throw new Exception("Configurações do Mercado Pago não encontradas no painel.");
        }

        $methodType = $paymentMethod === 'debit_card' ? 'debit_card' : 'credit_card';

        $response = Http::withToken($token)
            ->post("https://api.mercadopago.com/point/integration-api/devices/{$tefDeviceId}/payment-intents", [
                'amount' => (int) ($amount * 100), // MP Point Integration API exige valores em centavos
                'payment' => [
                    'type' => $methodType,
                    'installments' => 1,
                ],
                'additional_info' => [
                    'external_reference' => uniqid('TEF_'),
                    'print_on_terminal' => true
                ]
            ]);

        if ($response->successful()) {
            return $response->json('id');
        }

        // TRATAMENTO KIOSK-FIRST: Maquininha desvinculada ou sem internet
        if ($response->status() === 404 || str_contains(strtolower($response->body()), 'offline')) {
             Log::critical("ALERTA KIOSK: Terminal Point ID {$tefDeviceId} offline ou desvinculado.");
             throw new Exception("A maquininha está desligada, sem internet ou desvinculada da conta. Por favor, utilize o PIX.");
        }

        Log::error("Erro Mercado Pago TEF Create: " . $response->body());
        throw new Exception('A operadora recusou a transação ou o serviço está instável. Tente PIX.');
    }

    /**
     * Verifica o que o cliente fez na maquininha (Poling Seguro)
     */
    public function getPaymentIntentStatus(string $intentId): string
    {
        $token = $this->getAccessToken();
        if (!$token) return 'OPEN';

        $response = Http::withToken($token)
            ->get("https://api.mercadopago.com/point/integration-api/payment-intents/{$intentId}");

        if ($response->successful()) {
            return $response->json('state'); // Ex: OPEN, FINISHED, CANCELED, ERROR
        }

        return 'OPEN'; // Assume que ainda está aberto em caso de falha de rede da API
    }

    /**
     * Remove o valor da tela da maquininha se o cliente desistir no Totem
     */
    public function cancelIntent(string $tefDeviceId, string $intentId): bool
    {
        $token = $this->getAccessToken();
        if (!$token) return false;

        $response = Http::withToken($token)
            ->delete("https://api.mercadopago.com/point/integration-api/devices/{$tefDeviceId}/payment-intents/{$intentId}");

        return $response->successful();
    }
}