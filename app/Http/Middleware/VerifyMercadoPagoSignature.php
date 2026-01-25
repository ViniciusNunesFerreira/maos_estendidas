<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentSetting;
use Symfony\Component\HttpFoundation\Response;

class VerifyMercadoPagoSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Obter headers e assinatura
        $xSignature = $request->header('x-signature');
        $requestId  = $request->header('x-request-id');

        if (!$xSignature || !$requestId) {
            Log::warning('Webhook MP - Headers de assinatura ausentes', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Signature missing'], 403);
        }

        // 2. Parsear a assinatura (ts=...,v1=...)
        $parts = $this->parseSignature($xSignature);
        if (!isset($parts['ts']) || !isset($parts['v1'])) {
            return response()->json(['message' => 'Invalid signature format'], 403);
        }

        // 3. Obter a Secret Key do Banco de Dados (Cachear isso em produção é recomendado)
        $config = PaymentSetting::getMercadoPagoConfig();
        
        if (!$config || empty($config->webhook_secret)) {
            Log::error('Webhook MP - Configuração ou Secret não encontrada no banco');
            return response()->json(['message' => 'Server configuration error'], 500);
        }

        // 4. Validar Timestamp (Evitar Replay Attacks - tolerância de 5 minutos)
        // O Mercado Pago recomenda verificar se o ts não é muito antigo
        // mas em dev/testes as vezes isso atrapalha, em prod descomente:
        // if (abs(time() - $parts['ts']) > 300) {
        //     return response()->json(['message' => 'Request expired'], 403);
        // }

        // 5. Recriar o hash
        // Template: "id:[data.id];request-id:[x-request-id];ts:[ts];"
        $dataId = $request->input('data.id');
        if (!$dataId) {
             // Alguns eventos podem vir sem data.id, tratar conforme doc
             return $next($request); 
        }

        $manifest = "id:$dataId;request-id:$requestId;ts:{$parts['ts']};";
        
        $cyphedSignature = hash_hmac('sha256', $manifest, $config->webhook_secret);

        // 6. Comparar assinaturas
        if (!hash_equals($cyphedSignature, $parts['v1'])) {
            Log::warning('Webhook MP - Assinatura Inválida', [
                'received' => $parts['v1'],
                'computed' => $cyphedSignature,
                'manifest' => $manifest
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        return $next($request);
    }

    private function parseSignature(string $header): array
    {
        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = explode('=', $part, 2);
            $parts[trim($key)] = trim($value);
        }
        return $parts;
    }
}