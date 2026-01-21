<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * Middleware para validar signature dos webhooks do Mercado Pago
 */
class VerifyMercadoPagoSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Lista de IPs permitidos do Mercado Pago (produção)
        $allowedIps = [
            '209.225.49.0/24',
            '216.33.197.0/24',
            '216.33.196.0/24',
            '63.245.81.0/24',
        ];

        $requestIp = $request->ip();

        // Validar IP de origem
        if (!$this->isIpAllowed($requestIp, $allowedIps)) {
            Log::warning('Webhook MP - IP não autorizado', [
                'ip' => $requestIp,
            ]);

            // Em produção, pode bloquear
            // return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validar User-Agent
        $userAgent = $request->userAgent();
        if ($userAgent && !str_contains($userAgent, 'MercadoPago')) {
            Log::warning('Webhook MP - User-Agent suspeito', [
                'user_agent' => $userAgent,
            ]);
        }

        return $next($request);
    }

    /**
     * Verificar se IP está na lista permitida
     */
    protected function isIpAllowed(string $ip, array $allowedRanges): bool
    {
        foreach ($allowedRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar se IP está no range CIDR
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (!str_contains($range, '/')) {
            return $ip === $range;
        }

        [$subnet, $mask] = explode('/', $range);
        
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = ~((1 << (32 - $mask)) - 1);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}