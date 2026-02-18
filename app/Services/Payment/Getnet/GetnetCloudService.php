<?php

namespace App\Services\Payment\Getnet;

use App\Models\GetnetTransaction;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\PointDevice;
use App\Events\Payment\GetnetPaymentStatusUpdated;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service para integração com Getnet Cloud API
 * 
 * Responsável por enviar comandos de pagamento para terminais Getnet via Cloud
 * e processar respostas/webhooks da plataforma.
 */
class GetnetCloudService
{
    private string $apiUrl;
    private string $sellerId;
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;
    private ?\DateTime $tokenExpiresAt = null;

    public function __construct()
    {
        $this->apiUrl = config('services.getnet.api_url');
        $this->sellerId = config('services.getnet.seller_id');
        $this->clientId = config('services.getnet.client_id');
        $this->clientSecret = config('services.getnet.client_secret');
    }

    // =========================================================
    // AUTENTICAÇÃO
    // =========================================================

    /**
     * Obtém access token OAuth da Getnet
     * Cache de token em memória para otimizar chamadas
     */
    private function getAccessToken(): string
    {
        // Verifica se token ainda é válido
        if ($this->accessToken && $this->tokenExpiresAt && now() < $this->tokenExpiresAt) {
            return $this->accessToken;
        }

        try {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->apiUrl}/auth/oauth/v2/token", [
                    'scope' => 'oob',
                    'grant_type' => 'client_credentials',
                ]);

            if (!$response->successful()) {
                Log::error('Getnet: Falha na autenticação', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Falha ao autenticar com Getnet');
            }

            $data = $response->json();
            $this->accessToken = $data['access_token'];
            
            // Define expiração com margem de segurança (5 minutos antes)
            $expiresIn = $data['expires_in'] - 300;
            $this->tokenExpiresAt = now()->addSeconds($expiresIn);

            return $this->accessToken;

        } catch (\Exception $e) {
            Log::error('Getnet: Erro ao obter token', ['error' => $e->getMessage()]);
            throw new \Exception('Erro ao autenticar com Getnet: ' . $e->getMessage());
        }
    }

    // =========================================================
    // CRIAÇÃO DE PAGAMENTO NO TERMINAL
    // =========================================================

    /**
     * Cria um pagamento via terminal Getnet (Cloud API)
     * 
     * @param Order $order Pedido a ser pago
     * @param string $paymentMethod Método: 'pix', 'credit_card', 'debit_card'
     * @param string $terminalId ID do terminal Getnet
     * @param int $installments Número de parcelas (apenas para crédito)
     * @return GetnetTransaction
     */
    public function createTerminalPayment(
        Order $order,
        string $paymentMethod,
        string $terminalId,
        int $installments = 1
    ): GetnetTransaction {
        
        // Busca o device para obter informações
        $pointDevice = PointDevice::where('device_id', $terminalId)
            ->enabledForPdv()
            ->first();

        if (!$pointDevice) {
            throw new \Exception("Terminal {$terminalId} não encontrado ou inativo");
        }

        // Cria PaymentIntent
        $paymentIntent = $this->createPaymentIntent($order, $paymentMethod, $installments);

        // Cria registro de transação
        $transaction = GetnetTransaction::create([
            'payment_intent_id' => $paymentIntent->id,
            'order_id' => $order->id,
            'point_device_id' => $pointDevice->id,
            'terminal_id' => $terminalId,
            'seller_id' => $this->sellerId,
            'payment_method' => $paymentMethod,
            'amount' => $order->total,
            'currency' => 'BRL',
            'installments' => $installments,
            'status' => 'created',
            'pdv_device_id' => $order->device_id,
            'operator_name' => $order->createdBy?->name,
        ]);

        try {
            // Envia para terminal via Cloud API
            $this->sendPaymentToTerminal($transaction);
            
            return $transaction->fresh();

        } catch (\Exception $e) {
            $transaction->markAsError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Envia comando de pagamento para o terminal via API Cloud
     */
    private function sendPaymentToTerminal(GetnetTransaction $transaction): void
    {
        $token = $this->getAccessToken();
        
        // Prepara payload conforme documentação Getnet Cloud API
        $payload = $this->buildTerminalPaymentPayload($transaction);
        
        // Registra request
        $transaction->recordApiRequest($payload);
        $transaction->incrementAttempts();

        try {
            $response = Http::withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'seller_id' => $this->sellerId,
                ])
                ->timeout(30)
                ->post("{$this->apiUrl}/v1/terminals/payments", $payload);

            $responseData = $response->json();
            $transaction->recordApiResponse($responseData);

            if (!$response->successful()) {
                $errorMessage = $responseData['message'] ?? 'Erro ao enviar pagamento para terminal';
                
                Log::error('Getnet: Erro ao criar pagamento no terminal', [
                    'transaction_id' => $transaction->id,
                    'status' => $response->status(),
                    'error' => $errorMessage,
                    'response' => $responseData,
                ]);

                throw new \Exception($errorMessage);
            }

            // Atualiza com ID do pagamento Getnet
            $transaction->update([
                'payment_id' => $responseData['payment_id'] ?? null,
                'status' => 'waiting_terminal',
                'sent_to_terminal_at' => now(),
            ]);

            // Registra comunicação no device
            $transaction->pointDevice?->recordCommunication();

            Log::info('Getnet: Pagamento enviado para terminal com sucesso', [
                'transaction_id' => $transaction->id,
                'payment_id' => $transaction->payment_id,
                'terminal_id' => $transaction->terminal_id,
            ]);

            // Dispara evento de status atualizado
            event(new GetnetPaymentStatusUpdated($transaction));

        } catch (\Exception $e) {
            Log::error('Getnet: Exception ao enviar para terminal', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            
            $transaction->markAsError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Constrói payload para envio ao terminal
     */
    private function buildTerminalPaymentPayload(GetnetTransaction $transaction): array
    {
        $payload = [
            'seller_id' => $this->sellerId,
            'terminal_id' => $transaction->terminal_id,
            'amount' => (int)($transaction->amount * 100), // Converte para centavos
            'currency_code' => 'BRL',
            'order_id' => $transaction->order->order_number,
        ];

        // Adiciona método de pagamento específico
        switch ($transaction->payment_method) {
            case 'credit_card':
                $payload['payment_type'] = 'CREDIT';
                if ($transaction->installments > 1) {
                    $payload['installments'] = $transaction->installments;
                }
                break;

            case 'debit_card':
                $payload['payment_type'] = 'DEBIT';
                break;

            case 'pix':
                $payload['payment_type'] = 'PIX';
                break;
        }

        // Adiciona metadados úteis
        $payload['metadata'] = [
            'pdv_device_id' => $transaction->pdv_device_id,
            'order_number' => $transaction->order->order_number,
            'customer_type' => $transaction->order->customer_type,
        ];

        return $payload;
    }

    // =========================================================
    // WEBHOOK E PROCESSAMENTO DE RETORNO
    // =========================================================

    /**
     * Processa webhook recebido da Getnet
     */
    public function processWebhook(array $payload): GetnetTransaction
    {
        $paymentId = $payload['payment_id'] ?? null;
        
        if (!$paymentId) {
            throw new \Exception('Webhook inválido: payment_id não encontrado');
        }

        $transaction = GetnetTransaction::where('payment_id', $paymentId)->first();

        if (!$transaction) {
            Log::warning('Getnet: Transação não encontrada para webhook', [
                'payment_id' => $paymentId,
            ]);
            throw new \Exception("Transação não encontrada para payment_id: {$paymentId}");
        }

        // Registra webhook
        $transaction->recordWebhookPayload($payload);

        // Processa status
        $this->processWebhookStatus($transaction, $payload);

        return $transaction->fresh();
    }

    /**
     * Processa status do webhook
     */
    private function processWebhookStatus(GetnetTransaction $transaction, array $payload): void
    {
        $status = strtolower($payload['status'] ?? '');
        $statusDetail = $payload['status_detail'] ?? null;

        Log::info('Getnet: Processando webhook', [
            'transaction_id' => $transaction->id,
            'payment_id' => $transaction->payment_id,
            'status' => $status,
            'status_detail' => $statusDetail,
        ]);

        switch ($status) {
            case 'approved':
            case 'authorized':
                $this->handleApprovedPayment($transaction, $payload);
                break;

            case 'denied':
            case 'declined':
                $this->handleDeniedPayment($transaction, $payload);
                break;

            case 'cancelled':
            case 'canceled':
                $this->handleCancelledPayment($transaction, $payload);
                break;

            case 'processing':
                $transaction->update(['status' => 'processing']);
                break;

            case 'pending':
                $transaction->update(['status' => 'pending']);
                break;

            default:
                Log::warning('Getnet: Status desconhecido no webhook', [
                    'transaction_id' => $transaction->id,
                    'status' => $status,
                ]);
        }

        // Sempre dispara evento de atualização
        event(new GetnetPaymentStatusUpdated($transaction));
    }

    /**
     * Trata pagamento aprovado
     */
    private function handleApprovedPayment(GetnetTransaction $transaction, array $payload): void
    {
        $authorizationData = [
            'nsu' => $payload['nsu'] ?? null,
            'authorization_code' => $payload['authorization_code'] ?? null,
            'acquirer_transaction_id' => $payload['acquirer_transaction_id'] ?? null,
            'card_brand' => $payload['card_brand'] ?? null,
            'card_last_digits' => $payload['card_last_digits'] ?? null,
        ];

        $transaction->markAsApproved($authorizationData);

        // Atualiza PaymentIntent
        $transaction->paymentIntent->markAsApproved();

        // Marca pedido como pago
        $transaction->order->markAsPaid();

        // Registra no device
        $transaction->pointDevice?->recordPayment($transaction->amount);

        Log::info('Getnet: Pagamento aprovado', [
            'transaction_id' => $transaction->id,
            'order_id' => $transaction->order_id,
            'nsu' => $transaction->nsu,
            'authorization_code' => $transaction->authorization_code,
        ]);
    }

    /**
     * Trata pagamento negado
     */
    private function handleDeniedPayment(GetnetTransaction $transaction, array $payload): void
    {
        $denialReason = $payload['denial_reason'] ?? $payload['status_detail'] ?? 'Pagamento negado';
        $statusDetail = $payload['status_detail'] ?? null;

        $transaction->markAsDenied($denialReason, $statusDetail);
        $transaction->paymentIntent->markAsRejected($denialReason);

        Log::warning('Getnet: Pagamento negado', [
            'transaction_id' => $transaction->id,
            'order_id' => $transaction->order_id,
            'reason' => $denialReason,
        ]);
    }

    /**
     * Trata pagamento cancelado
     */
    private function handleCancelledPayment(GetnetTransaction $transaction, array $payload): void
    {
        $reason = $payload['cancellation_reason'] ?? 'Cancelado pelo usuário';
        
        $transaction->markAsCancelled($reason);
        $transaction->paymentIntent->markAsCancelled($reason);

        Log::info('Getnet: Pagamento cancelado', [
            'transaction_id' => $transaction->id,
            'reason' => $reason,
        ]);
    }

    // =========================================================
    // CONSULTA E POLLING
    // =========================================================

    /**
     * Consulta status de um pagamento (fallback se webhook falhar)
     */
    public function checkPaymentStatus(GetnetTransaction $transaction): GetnetTransaction
    {
        if (!$transaction->payment_id) {
            throw new \Exception('Transaction não possui payment_id para consulta');
        }

        $token = $this->getAccessToken();

        try {
            $response = Http::withToken($token)
                ->withHeaders(['seller_id' => $this->sellerId])
                ->get("{$this->apiUrl}/v1/payments/{$transaction->payment_id}");

            if (!$response->successful()) {
                throw new \Exception('Erro ao consultar status do pagamento');
            }

            $data = $response->json();
            $this->processWebhookStatus($transaction, $data);

            return $transaction->fresh();

        } catch (\Exception $e) {
            Log::error('Getnet: Erro ao consultar status', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cancela um pagamento pendente no terminal
     */
    public function cancelTerminalPayment(GetnetTransaction $transaction, string $reason = 'Cancelado pelo operador'): void
    {
        if ($transaction->is_finalized) {
            throw new \Exception('Não é possível cancelar uma transação finalizada');
        }

        if (!$transaction->payment_id) {
            // Se ainda não foi enviado, apenas marca como cancelado
            $transaction->markAsCancelled($reason);
            $transaction->paymentIntent->markAsCancelled($reason);
            return;
        }

        $token = $this->getAccessToken();

        try {
            $response = Http::withToken($token)
                ->withHeaders(['seller_id' => $this->sellerId])
                ->post("{$this->apiUrl}/v1/payments/{$transaction->payment_id}/cancel", [
                    'reason' => $reason,
                ]);

            if ($response->successful()) {
                $transaction->markAsCancelled($reason);
                $transaction->paymentIntent->markAsCancelled($reason);
                
                event(new GetnetPaymentStatusUpdated($transaction));
            } else {
                throw new \Exception('Erro ao cancelar pagamento no terminal');
            }

        } catch (\Exception $e) {
            Log::error('Getnet: Erro ao cancelar pagamento', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Cria PaymentIntent para rastreamento
     */
    private function createPaymentIntent(Order $order, string $paymentMethod, int $installments): PaymentIntent
    {
        return PaymentIntent::create([
            'order_id' => $order->id,
            'integration_type' => 'getnet_cloud',
            'payment_method' => $paymentMethod,
            'amount' => $order->total,
            'installments' => $installments,
            'status' => 'created',
        ]);
    }

    /**
     * Valida assinatura do webhook (security)
     */
    public function validateWebhookSignature(string $signature, string $payload): bool
    {
        $webhookSecret = config('services.getnet.webhook_secret');
        
        if (!$webhookSecret) {
            Log::warning('Getnet: Webhook secret não configurado');
            return true; // Em dev pode aceitar sem validação
        }

        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        
        return hash_equals($expectedSignature, $signature);
    }
}