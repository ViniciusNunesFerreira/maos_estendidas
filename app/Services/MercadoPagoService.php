<?php

namespace App\Services;

use App\Models\PaymentSetting;
use App\Exceptions\MercadoPagoException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service principal para integração com Mercado Pago
 * 
 * CORREÇÃO: Não lança exception no construtor para permitir
 * acesso à página de configuração quando MP não está configurado
 */
class MercadoPagoService
{
    protected ?PaymentSetting $config = null;
    protected string $baseUrl = 'https://api.mercadopago.com';
    protected int $timeout = 30;
    protected bool $isConfigured = false;

    /**
     * Construtor - carrega config se disponível, mas NÃO falha
     */
    public function __construct()
    {
        $this->loadConfig();
    }

    // =========================================================
    // CONFIGURAÇÃO
    // =========================================================

    /**
     * Carregar configuração ativa do Mercado Pago
     * NÃO lança exception, apenas marca como não configurado
     */
    protected function loadConfig(): void
    {
        try {
            $this->config = PaymentSetting::getMercadoPagoConfig();

            if ($this->config && $this->config->isConfigured()) {
                $this->isConfigured = true;
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao carregar config do Mercado Pago', [
                'error' => $e->getMessage(),
            ]);
            $this->isConfigured = false;
        }
    }

    /**
     * Verificar se o MP está configurado
     * Usar este método ANTES de chamar métodos da API
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured && $this->config !== null;
    }

    /**
     * Obter configuração atual
     * 
     * @throws MercadoPagoException se não configurado
     */
    public function getConfig(): PaymentSetting
    {
        $this->ensureConfigured();
        return $this->config;
    }

    /**
     * Garantir que está configurado antes de executar operações
     * 
     * @throws MercadoPagoException se não configurado
     */
    protected function ensureConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new MercadoPagoException(
                'Mercado Pago não configurado. Configure em Admin > Configurações > Pagamentos.'
            );
        }
    }

    /**
     * Verificar se está em modo sandbox
     */
    public function isSandbox(): bool
    {
        return $this->config?->isSandbox() ?? false;
    }

    /**
     * Verificar se está em produção
     */
    public function isProduction(): bool
    {
        return $this->config?->isProduction() ?? false;
    }

    // =========================================================
    // HTTP CLIENT BASE
    // =========================================================

    /**
     * Fazer requisição GET
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, [
            'query' => $params,
        ]);
    }

    /**
     * Fazer requisição POST
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, [
            'json' => $data,
        ]);
    }

    /**
     * Fazer requisição PUT
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, [
            'json' => $data,
        ]);
    }

    /**
     * Fazer requisição DELETE
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Requisição HTTP base com retry e error handling
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        // CRITICAL: Garantir que está configurado antes de fazer request
        $this->ensureConfigured();

        $url = $this->baseUrl . $endpoint;
        
        // Headers padrão
        $headers = [
            'Authorization' => 'Bearer ' . $this->config->access_token,
            'Content-Type' => 'application/json',
            'X-Idempotency-Key' => $this->generateIdempotencyKey(),
        ];

        // Adicionar header de sandbox se necessário
        if ($this->isSandbox()) {
            $headers['X-Test-Scope'] = 'sandbox';
        }

        // Merge headers
        $options['headers'] = array_merge($headers, $options['headers'] ?? []);

        // Log da requisição (sem token)
        $this->logRequest($method, $url, $options);

        try {
            // Fazer requisição com retry (max 3 tentativas)
            $response = Http::timeout($this->timeout)
                ->retry(3, 1000, function ($exception, $request) {
                    // Retry apenas em erros 5xx ou timeout
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException
                        || ($exception instanceof \Illuminate\Http\Client\RequestException 
                            && $exception->response->status() >= 500);
                })
                ->withHeaders($options['headers'])
                ->send($method, $url, $options);

            // Log da resposta
            $this->logResponse($response->status(), $response->json());

            // Verificar se obteve sucesso
            if ($response->successful()) {
                return $response->json();
            }

            // Erro do Mercado Pago
            $this->handleMercadoPagoError($response);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Mercado Pago - Erro de conexão', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new MercadoPagoException(
                'Falha na conexão com Mercado Pago. Tente novamente em alguns instantes.',
                500,
                $e
            );
        } catch (\Exception $e) {
            Log::error('Mercado Pago - Erro inesperado', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new MercadoPagoException(
                'Erro ao comunicar com Mercado Pago: ' . $e->getMessage(),
                500,
                $e
            );
        }

        // Nunca deve chegar aqui, mas por segurança
        throw new MercadoPagoException('Resposta inesperada do Mercado Pago');
    }

    // =========================================================
    // PAYMENTS API
    // =========================================================

    /**
     * Criar pagamento
     */
    public function createPayment(array $data): array
    {
        return $this->post('/v1/payments', $data);
    }

    /**
     * Buscar pagamento por ID
     */
    public function getPayment(string $paymentId): array
    {
        return $this->get("/v1/payments/{$paymentId}");
    }

    /**
     * Cancelar/Reembolsar pagamento
     */
    public function refundPayment(string $paymentId): array
    {
        return $this->post("/v1/payments/{$paymentId}/refunds");
    }

    // =========================================================
    // POINT API (TEF)
    // =========================================================

    /**
     * Criar payment intent no Point
     */
    public function createPointIntent(string $deviceId, array $data): array
    {
        return $this->post(
            "/point/integration-api/devices/{$deviceId}/payment-intents",
            $data
        );
    }

    /**
     * Buscar payment intent do Point
     */
    public function getPointIntent(string $intentId): array
    {
        return $this->get("/point/integration-api/payment-intents/{$intentId}");
    }

    /**
     * Cancelar payment intent do Point
     */
    public function cancelPointIntent(string $intentId): array
    {
        return $this->delete("/point/integration-api/payment-intents/{$intentId}");
    }

    // =========================================================
    // MERCHANT ORDERS API
    // =========================================================

    /**
     * Buscar merchant order
     */
    public function getMerchantOrder(string $orderId): array
    {
        return $this->get("/merchant_orders/{$orderId}");
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Gerar chave de idempotência
     */
    protected function generateIdempotencyKey(): string
    {
        return uniqid('mp_', true) . '_' . time();
    }

    /**
     * Log de requisição (sem dados sensíveis)
     */
    protected function logRequest(string $method, string $url, array $options): void
    {
        // Remove token dos logs
        $safeOptions = $options;
        if (isset($safeOptions['headers']['Authorization'])) {
            $safeOptions['headers']['Authorization'] = 'Bearer ***';
        }

        Log::info('Mercado Pago - Requisição', [
            'method' => $method,
            'url' => $url,
            'environment' => $this->isSandbox() ? 'sandbox' : 'production',
            'options' => $safeOptions,
        ]);
    }

    /**
     * Log de resposta
     */
    protected function logResponse(int $status, ?array $data): void
    {
        Log::info('Mercado Pago - Resposta', [
            'status' => $status,
            'data' => $data,
        ]);
    }

    /**
     * Tratar erros do Mercado Pago
     */
    protected function handleMercadoPagoError($response): void
    {
        $status = $response->status();
        $body = $response->json();

        // Extrair mensagem de erro
        $message = $body['message'] ?? 'Erro desconhecido';
        $cause = $body['cause'][0] ?? null;
        
        if ($cause) {
            $message = $cause['description'] ?? $message;
        }

        Log::error('Mercado Pago - Erro da API', [
            'status' => $status,
            'body' => $body,
        ]);

        // Mapear erros comuns
        $userMessage = match($status) {
            400 => 'Dados inválidos: ' . $message,
            401 => 'Credenciais do Mercado Pago inválidas. Verifique a configuração.',
            403 => 'Acesso negado pelo Mercado Pago.',
            404 => 'Recurso não encontrado no Mercado Pago.',
            429 => 'Muitas requisições. Aguarde alguns instantes.',
            500, 502, 503, 504 => 'Mercado Pago temporariamente indisponível. Tente novamente.',
            default => 'Erro ao processar pagamento: ' . $message,
        };

        throw new MercadoPagoException($userMessage, $status, null, $body);
    }

    /**
     * Validar webhook signature
     */
    public function validateWebhookSignature(array $headers, string $payload): bool
    {
        // Mercado Pago envia signature no header x-signature
        $signature = $headers['x-signature'] ?? $headers['X-Signature'] ?? null;
        
        if (!$signature) {
            return false;
        }

        // Se não tem config, aceitar (não ideal, mas permite setup inicial)
        if (!$this->isConfigured()) {
            Log::warning('Webhook recebido sem config do MP');
            return true;
        }

        // Validar usando webhook secret (se configurado)
        if ($this->config->webhook_secret) {
            $expectedSignature = hash_hmac('sha256', $payload, $this->config->webhook_secret);
            return hash_equals($expectedSignature, $signature);
        }

        // Se não tem webhook secret configurado, aceita (não recomendado em produção)
        Log::warning('Webhook recebido sem validação de signature (webhook_secret não configurado)');
        return true;
    }

    /**
     * Testar conexão com Mercado Pago
     */
    public function testConnection(): array
    {
        try {
            // Verificar se está configurado
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Mercado Pago não configurado. Configure credenciais primeiro.',
                ];
            }

            // Fazer uma requisição simples para validar credenciais
            $response = $this->get('/v1/payment_methods');

            return [
                'success' => true,
                'message' => 'Conexão com Mercado Pago estabelecida com sucesso!',
                'environment' => $this->isSandbox() ? 'Sandbox (Teste)' : 'Produção',
                'payment_methods_count' => count($response),
            ];

        } catch (MercadoPagoException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ];
        }
    }
}