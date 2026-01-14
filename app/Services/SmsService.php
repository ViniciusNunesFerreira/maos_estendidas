<?php
// app/Services/SmsService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $apiUrl;
    private string $apiKey;
    private string $sender;

    public function __construct()
    {
        $this->apiUrl = config('casalar.sms.api_url');
        $this->apiKey = config('casalar.sms.api_key');
        $this->sender = config('casalar.sms.sender', 'CASALAR');
    }

    /**
     * Enviar SMS
     */
    public function send(string $phone, string $message): bool
    {
        try {
            // Formatar telefone
            $phone = $this->formatPhone($phone);
            
            // Em desenvolvimento, apenas logar
            if (app()->environment('local', 'testing')) {
                Log::info("SMS simulado", [
                    'phone' => $phone,
                    'message' => $message,
                ]);
                return true;
            }
            
            // Enviar via API (exemplo com Zenvia/SMS.to)
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'from' => $this->sender,
                'to' => $phone,
                'text' => $message,
            ]);
            
            if ($response->successful()) {
                Log::info("SMS enviado", [
                    'phone' => $phone,
                    'response' => $response->json(),
                ]);
                return true;
            }
            
            Log::error("Erro ao enviar SMS", [
                'phone' => $phone,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error("Exceção ao enviar SMS", [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Formatar telefone para envio
     */
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        
        // Adicionar código do país se necessário
        if (strlen($phone) === 11) {
            $phone = '55' . $phone;
        }
        
        return $phone;
    }
}