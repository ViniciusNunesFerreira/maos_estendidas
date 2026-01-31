<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZApiApiService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = config('services.zapi.base_url');
        $this->token = config('services.zapi.token');
    }

    public function checkValidity(string $number): bool
    {
        // Formata para o padrão internacional completo (55 + DDD + Número)
        $formattedNumber = '55' . $number;

        // 2. Montagem do ENDPOINT CORRETO
        $endpoint = "{$this->baseUrl}phone-exists/{$formattedNumber}";

        try {
            $response = Http::withHeaders([
                'client-token' => $this->token,
            ])
            ->timeout(5)
            ->get($endpoint);

            $data = $response->json();

            if (!$response->successful()) {
                return false;
            }

            if (is_array($data) && count($data) > 0) {
                return filter_var($data['exists'] ?? false, FILTER_VALIDATE_BOOLEAN);
            }
            
            return false;


        } catch (\Exception $e) {
            Log::error("Erro na checagem de número via Z-API: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envia uma mensagem de texto simples usando a Z-API.
     * @param string $recipientNumber Número do WhatsApp do cliente (apenas dígitos).
     * @param string $message O texto completo da mensagem (OTP ou Confirmação).
     * @return bool
     */
    public function sendMessage(string $recipientNumber, string $message): bool
    {
       
        $formattedNumber = '55' . $recipientNumber;
        
        $payload = [
            'phone' => $formattedNumber,
            'message' => $message,
        ];

        try {
            $response = Http::withHeaders([
                'client-token' => $this->token, 
            ])->post("{$this->baseUrl}send-text", $payload);

            if ($response->successful()) {
                return true;
            }

            return false;

        } catch (\Exception $e) {
            return false;
        }
    }
}