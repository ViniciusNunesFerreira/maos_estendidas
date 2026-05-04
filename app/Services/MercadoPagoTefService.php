<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentSetting;
use Exception;

class MercadoPagoTefService
{
    private function getAccessToken(): ?string
    {
        $settings = PaymentSetting::getMercadoPagoConfig();
        return $settings ? $settings->access_token : config('services.mercadopago.access_token');
    }

    public function createIntent(string $tefDeviceId, float $amount, string $paymentMethod): ?string
    {
        $token = $this->getAccessToken();
        if (!$token) {
            throw new \Exception("Configurações do Mercado Pago não encontradas no painel.");
        }

        $methodType = $paymentMethod === 'debit_card' ? 'debit_card' : 'credit_card';

        $payload = [
            'amount' => (int) ($amount * 100),
            'description' => 'Venda Totem Autoatendimento',
            'payment' => [
                'type' => $methodType,
            ],
            'additional_info' => [
                'external_reference' => uniqid('TEF_'),
                'print_on_terminal' => true
            ]
        ];

        if ($methodType === 'credit_card') {
            $payload['payment']['installments'] = 1;
            $payload['payment']['installments_cost'] = 'buyer'; 
        }

        $response = \Illuminate\Support\Facades\Http::withToken($token)
            ->post("https://api.mercadopago.com/point/integration-api/devices/{$tefDeviceId}/payment-intents", $payload);

        if ($response->successful()) {
            return $response->json('id');
        }

        if ($response->status() === 404 || str_contains(strtolower($response->body()), 'offline')) {
             Log::critical("ALERTA KIOSK: Terminal Point ID {$tefDeviceId} offline ou desvinculado.");
             throw new \Exception("A maquininha está desligada ou sem internet. Por favor, utilize o PIX.");
        }

        Log::error("Erro Mercado Pago TEF Create: " . $response->body());
        throw new \Exception('A operadora recusou a transação. Tente via PIX.');
    }

    public function getPaymentIntentStatus(string $intentId): string
    {
        $token = $this->getAccessToken();
        if (!$token) return 'OPEN';

        $response = Http::withToken($token)
            ->get("https://api.mercadopago.com/point/integration-api/payment-intents/{$intentId}");

        if ($response->successful()) {
            return $response->json('state');
        }

        return 'OPEN';
    }

    public function cancelIntent(string $tefDeviceId, string $intentId): void
    {
        $token = $this->getAccessToken();
        if (!$token) return;

        $response = Http::withToken($token)
            ->delete("https://api.mercadopago.com/point/integration-api/devices/{$tefDeviceId}/payment-intents/{$intentId}");

        Log::info("TEF Cancel Request", [
            'device' => $tefDeviceId,
            'intent' => $intentId,
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        // O Mercado Pago retorna 409 quando a maquininha está na tela de inserção de cartão (ON_TERMINAL)
        if ($response->status() === 409) {
            throw new \Exception("A operação já está na tela da maquininha. Pressione a tecla vermelha (X) no teclado dela para cancelar.", 409);
        }

        // Ignoramos 404 (já cancelado/inexistente) e 400 (estado inválido para cancelamento)
        if (!$response->successful() && !in_array($response->status(), [404, 400])) {
            throw new \Exception("Falha de comunicação ao tentar cancelar na maquininha.");
        }
    }
}