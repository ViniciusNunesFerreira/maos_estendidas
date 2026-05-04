<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class MercadoPagoTefService
{
    private $accessToken;

    public function __construct()
    {
        $this->accessToken = config('services.mercadopago.access_token');
    }

    /**
     * Aciona a maquininha Point TEF online.
     */
    public function createIntent(string $deviceId, float $amount, string $paymentMethod): ?string
    {
        // Mercado Pago exige o formato 'credit_card' ou 'debit_card'
        $methodType = $paymentMethod === 'debit_card' ? 'debit_card' : 'credit_card';

        $response = Http::withToken($this->accessToken)
            ->post("https://api.mercadopago.com/point/integration-api/devices/{$deviceId}/payment-intents", [
                'amount' => (int) ($amount * 100), // Em centavos se aplicável, confirmar docs do MP
                'payment' => [
                    'type' => $methodType,
                    'installments' => 1,
                ],
                'additional_info' => [
                    'external_reference' => uniqid(),
                    'print_on_terminal' => true
                ]
            ]);

        if ($response->successful()) {
            return $response->json('id'); // Retorna o ID da intenção na maquininha
        }

        throw new Exception('Erro ao conectar com a Maquininha TEF: ' . $response->body());
    }

    /**
     * Verifica se o cliente já inseriu o cartão e aprovou o pagamento
     */
    public function getPaymentIntentStatus(string $intentId): string
    {
        $response = Http::withToken($this->accessToken)
            ->get("https://api.mercadopago.com/point/integration-api/payment-intents/{$intentId}");

        if ($response->successful()) {
            return $response->json('state'); // Ex: 'OPEN', 'FINISHED', 'CANCELED', 'ERROR'
        }

        return 'OPEN';
    }

    /**
     * Se o cliente cancelar no Totem, cancelamos o comando na Maquininha
     */
    public function cancelIntent(string $deviceId, string $intentId): bool
    {
        $response = Http::withToken($this->accessToken)
            ->delete("https://api.mercadopago.com/point/integration-api/devices/{$deviceId}/payment-intents/{$intentId}");

        return $response->successful();
    }
}