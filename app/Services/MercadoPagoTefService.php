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
    /**
     * Aciona a maquininha Point TEF online e injeta o valor na tela.
     */
    public function createIntent(string $tefDeviceId, float $amount, string $paymentMethod): ?string
    {
        $token = $this->getAccessToken();
        if (!$token) {
            throw new \Exception("Configurações do Mercado Pago não encontradas no painel.");
        }

        $methodType = $paymentMethod === 'debit_card' ? 'debit_card' : 'credit_card';

        // 1. Monta o Payload Base (Agora com Description para blindar o firmware)
        $payload = [
            'amount' => (int) ($amount * 100), // MP exige centavos
            'description' => 'Venda Totem Autoatendimento', // Obrigatório em alguns firmwares para aceitar a trava
            'payment' => [
                'type' => $methodType,
            ],
            'additional_info' => [
                'external_reference' => uniqid('TEF_'),
                'print_on_terminal' => true
            ]
        ];

        // 2. Trava Absoluta para o Crédito
        if ($methodType === 'credit_card') {
            $payload['payment']['installments'] = 1; // 1 Parcela (À Vista)
            $payload['payment']['installments_cost'] = 'buyer'; 
        }

        // 3. Dispara a requisição
        $response = \Illuminate\Support\Facades\Http::withToken($token)
            ->post("https://api.mercadopago.com/point/integration-api/devices/{$tefDeviceId}/payment-intents", $payload);

        if ($response->successful()) {
            return $response->json('id');
        }

        // Tratamento de máquina offline
        if ($response->status() === 404 || str_contains(strtolower($response->body()), 'offline')) {
             \Illuminate\Support\Facades\Log::critical("ALERTA KIOSK: Terminal Point ID {$tefDeviceId} offline ou desvinculado.");
             throw new \Exception("A maquininha está desligada ou sem internet. Por favor, utilize o PIX.");
        }

        \Illuminate\Support\Facades\Log::error("Erro Mercado Pago TEF Create: " . $response->body());
        throw new \Exception('A operadora recusou a transação. Tente via PIX.');
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