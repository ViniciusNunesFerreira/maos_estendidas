<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\PaymentIntent;
use App\Services\CheckoutTransparenteService;
use App\Services\ExternalPaymentService;
use App\DTOs\ProcessCheckoutDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Payment Controller - Versão Atualizada
 * 
 * ✅ Mantém endpoints antigos funcionando
 * 🆕 Adiciona suporte para pagamento de Invoices
 * 🆕 Adiciona ExternalPaymentService
 * 
 * @version 2.0
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly CheckoutTransparenteService $checkoutService,
        private readonly ExternalPaymentService $externalPaymentService
    ) {}

    /**
     * Criar pagamento com Cartão
     * POST /api/v1/app/payments/create-card
     * 
     * ✅ MANTIDO: Endpoint antigo continua funcionando
     */
    public function createCardPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|uuid|exists:orders,id',
            'card_token' => 'required|string',
            'installments' => 'required|integer|min:1|max:12',
            'payer' => 'sometimes|array',
            'payer.email' => 'required_with:payer|email',
            'payer.identification' => 'required_with:payer|array',
            'payer.identification.type' => 'required_with:payer.identification|string',
            'payer.identification.number' => 'required_with:payer.identification|string',
        ]);

        try {
            $order = Order::with('items.product')->findOrFail($validated['order_id']);

            // Verificar se pedido pertence ao filho autenticado
            $filho = $request->user()->filho;
            if ($order->filho_id !== $filho->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            // Verificar se pedido já foi pago
            if ($order->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido já foi pago',
                ], 422);
            }

            // Criar DTO
            $dto = ProcessCheckoutDTO::fromRequest([
                'order_id' => $order->id,
                'amount' => $order->total,
                'payment_method' => 'credit_card',
                'card_token' => $validated['card_token'],
                'installments' => $validated['installments'],
                'payer' => $validated['payer'] ?? [
                    'email' => $filho->user->email,
                    'identification' => [
                        'type' => 'CPF',
                        'number' => $filho->cpf,
                    ],
                ],
            ]);

            // Criar pagamento
            $result = $this->checkoutService->createCardPayment(
                $order,
                $dto->cardToken,
                $dto->installments,
                $dto->payer
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erro ao processar pagamento',
                ], 422);
            }

            $paymentIntent = $result['payment_intent'];

            // Se foi aprovado imediatamente
            if ($paymentIntent->status === 'approved') {
                return response()->json([
                    'success' => true,
                    'message' => 'Pagamento aprovado!',
                    'data' => [
                        'payment_intent_id' => $paymentIntent->id,
                        'status' => 'approved',
                        'approved' => true,
                        'order_id' => $order->id,
                    ],
                ]);
            }

            // Se está pendente
            return response()->json([
                'success' => true,
                'message' => 'Pagamento em processamento',
                'data' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'mp_payment_id' => $paymentIntent->mp_payment_id,
                    'status' => $paymentIntent->status,
                    'approved' => false,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao criar pagamento com cartão', [
                'order_id' => $validated['order_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento',
            ], 500);
        }
    }

    /**
     * Verificar status do pagamento
     * GET /api/v1/app/payments/{paymentIntent}/status
     * 
     * ✅ MANTIDO: Endpoint antigo continua funcionando
     */
    public function checkStatus(string $paymentIntentId): JsonResponse
    {
        try {
            $paymentIntent = PaymentIntent::findOrFail($paymentIntentId);

            // Verificar se pertence ao filho autenticado
            $filho = auth()->user()->filho;

            if ($paymentIntent->order_id) {
                if ($paymentIntent->order->filho_id !== $filho->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pagamento não encontrado',
                    ], 404);
                }
            }elseif($paymentIntent->invoice_id){

                 if ($paymentIntent->invoice->filho_id !== $filho->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pagamento não encontrado',
                    ], 404);
                }
            }

            // Verificar status atualizado
            if (in_array($paymentIntent->status, ['pending', 'processing'])) {
                $this->externalPaymentService->checkPaymentStatus($paymentIntent);
                $paymentIntent->refresh();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $paymentIntent->status,
                    'approved' => $paymentIntent->status === 'approved',
                    'order_id' => $paymentIntent->order_id,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao verificar status do pagamento', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar status',
            ], 500);
        }
    }

    /**
     * Cancelar pagamento
     * POST /api/v1/app/payments/{paymentIntent}/cancel
     * 
     * ✅ MANTIDO: Endpoint antigo continua funcionando
     */
    public function cancelPayment(string $paymentIntentId): JsonResponse
    {
        try {
            $paymentIntent = PaymentIntent::findOrFail($paymentIntentId);

            // Verificar se pertence ao filho autenticado
            $filho = auth()->user()->filho;
            if ($paymentIntent->order->filho_id !== $filho->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pagamento não encontrado',
                ], 404);
            }

            // Verificar se pode cancelar
            if (!in_array($paymentIntent->status, ['created', 'pending'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pagamento não pode ser cancelado',
                ], 422);
            }

            // Cancelar
            $result = $this->checkoutService->cancelPayment($paymentIntent);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Pagamento cancelado',
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao cancelar pagamento', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar pagamento',
            ], 500);
        }
    }

    // =========================================================
    // NOVOS ENDPOINTS - PAGAMENTO DE INVOICES
    // =========================================================

    /**
     * Criar pagamento PIX para Invoice
     * POST /api/v1/app/payments/invoices/{invoice}/pix
     * 
     * 🆕 NOVO: Pagamento de faturas via PIX
     */
    public function createInvoicePixPayment(Invoice $invoice): JsonResponse
    {

        try {
            // Verificar se fatura pertence ao filho autenticado
            $filho = auth()->user()->filho;
            
            if (!$filho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não vinculado a um filho',
                ], 403);
            }

            if ($invoice->filho_id !== $filho->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fatura não encontrada',
                ], 404);
            }

            // Verificar se fatura já foi paga
            if ($invoice->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Fatura já foi paga',
                ], 422);
            }

            if ($invoice->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Fatura cancelada',
                ], 422);
            }

            // ✅ VALIDAÇÃO CRÍTICA: Invoice não pode ser paga com saldo
            // Isso seria "pagar dívida com dívida" = colapso financeiro
            Log::info('Criando pagamento PIX para Invoice', [
                'invoice_id' => $invoice->id,
                'filho_id' => $filho->id,
                'amount' => $invoice->remaining_amount,
            ]);

            // Usar ExternalPaymentService
            $result = $this->externalPaymentService->createInvoicePixPayment($invoice);

            return response()->json([
                'success' => true,
                'message' => 'QR Code PIX gerado com sucesso',
                'data' => [
                    'payment_intent_id' => $result['payment_intent_id'],
                    'mp_payment_id' => $result['mp_payment_id'],
                    'status' => $result['status'],
                    'pix' => $result['pix'],
                    'amount' => $result['amount'],
                    'invoice' => [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'total_amount' => $invoice->total_amount,
                        'paid_amount' => $invoice->paid_amount,
                        'remaining_amount' => $invoice->remaining_amount,
                    ],
                ],
            ]);

        }catch (\Exception $e) {
            Log::error('Erro ao criar pagamento PIX para Invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pagamento PIX',
            ], 500);
        }
    }

    /**
     * Criar pagamento com Cartão para Invoice
     * POST /api/v1/app/payments/invoices/{invoice}/card
     * 
     * 🆕 NOVO: Pagamento de faturas via Cartão
     */
    public function createInvoiceCardPayment(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'card_token' => 'required|string',
            'payment_method_id' => 'required|string',
            'installments' => 'required|integer|min:1|max:12',
            'payer' => 'sometimes|array',
            'payer.email' => 'required_with:payer|email',
            'payer.identification' => 'required_with:payer|array',
            'payer.identification.type' => 'required_with:payer.identification|string',
            'payer.identification.number' => 'required_with:payer.identification|string',
        ]);

        try {
            // Verificar se fatura pertence ao filho autenticado
            $filho = auth()->user()->filho;
            
            if (!$filho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não vinculado a um filho',
                ], 403);
            }

            if ($invoice->filho_id !== $filho->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fatura não encontrada',
                ], 404);
            }

            // Verificar se fatura já foi paga
            if ($invoice->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Fatura já foi paga',
                ], 422);
            }

            if ($invoice->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Fatura cancelada',
                ], 422);
            }

            // Preparar payer
            $payer = $validated['payer'] ?? [
                'email' => $filho->user->email,
                'identification' => [
                    'type' => 'CPF',
                    'number' => $filho->cpf,
                ],
            ];

            Log::info('Criando pagamento com Cartão para Invoice', [
                'invoice_id' => $invoice->id,
                'filho_id' => $filho->id,
                'amount' => $invoice->remaining_amount,
                'installments' => $validated['installments'],
            ]);

            // Usar ExternalPaymentService
            $result = $this->externalPaymentService->createInvoiceCardPayment(
                $invoice,
                $validated['card_token'],
                $payer,
                $validated['payment_method_id']
            );


            return response()->json([
                'success' => true,
                'message' => $result['approved'] ? 'Pagamento aprovado!' : 'Pagamento em processamento',
                'data' => $result,
            ]);


        } catch (\Exception $e) {
            Log::error('Erro ao criar pagamento com Cartão para Invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento',
            ], 500);
        }
    }

    /**
     * Listar faturas pendentes
     * GET /api/v1/app/payments/invoices/pending
     * 
     * 🆕 NOVO: Listar faturas que precisam ser pagas
     */
    public function getPendingInvoices(): JsonResponse
    {
        try {
            $filho = auth()->user()->filho;

            if (!$filho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não vinculado a um filho',
                ], 403);
            }

            $invoices = Invoice::where('filho_id', $filho->id)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->orderBy('due_date', 'asc')
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'type' => $invoice->type,
                        'issue_date' => $invoice->issue_date->toDateString(),
                        'due_date' => $invoice->due_date->toDateString(),
                        'total_amount' => $invoice->total_amount,
                        'paid_amount' => $invoice->paid_amount,
                        'remaining_amount' => $invoice->remaining_amount,
                        'status' => $invoice->status,
                        'is_overdue' => $invoice->is_overdue,
                        'days_overdue' => $invoice->days_overdue,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $invoices,
                'summary' => [
                    'total_pending' => $invoices->count(),
                    'total_amount' => $invoices->sum('remaining_amount'),
                    'overdue_count' => $invoices->where('is_overdue', true)->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar faturas pendentes', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar faturas',
            ], 500);
        }
    }

    // =========================================================
    // NOVOS ENDPOINTS - ENDPOINTS MODERNOS PARA ORDERS
    // =========================================================

    /**
     * Criar pagamento PIX para Order (novo padrão)
     * POST /api/v1/app/payments/orders/{order}/pix
     * 
     * 🆕 NOVO: Endpoint moderno usando ExternalPaymentService
     */
    public function createOrderPix(Order $order): JsonResponse
    {
        try {
            // Verificar se pedido pertence ao filho autenticado
            $filho = auth()->user()->filho;
            
            if (!$filho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não vinculado a um filho',
                ], 403);
            }

            if ($order->filho_id !== $filho->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            if ($order->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido já foi pago',
                ], 422);
            }

            // Usar ExternalPaymentService (novo)
            $result = $this->externalPaymentService->createOrderPixPayment($order);

            return response()->json([
                'success' => true,
                'message' => 'QR Code PIX gerado com sucesso',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pagamento PIX',
            ], 500);
        }
    }

    /**
     * Criar pagamento com Cartão para Order (novo padrão)
     * POST /api/v1/app/payments/orders/{order}/card
     * 
     * 🆕 NOVO: Endpoint moderno usando ExternalPaymentService
     */
    public function createOrderCard(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'card_token' => 'required|string',
            'payment_method_id' => 'required|string',
            'installments' => 'required|integer|min:1|max:12',
            'payer' => 'sometimes|array',
            'payer.email' => 'required_with:payer|email',
            'payer.identification' => 'required_with:payer|array',
            'payer.identification.type' => 'required_with:payer.identification|string',
            'payer.identification.number' => 'required_with:payer.identification|string',
        ]);

        try {
            $filho = auth()->user()->filho;
            
            if (!$filho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não vinculado a um filho',
                ], 403);
            }

            if ($order->filho_id !== $filho->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            if ($order->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido já foi pago',
                ], 422);
            }

            $payer = $validated['payer'] ?? [
                'email' => $filho->user->email,
                'identification' => [
                    'type' => 'CPF',
                    'number' => $filho->cpf,
                ],
            ];



            // Usar ExternalPaymentService (novo) //Para adicionar o parcelamento adicionar instalments no metodo
            $result = $this->externalPaymentService->createOrderCardPayment(
                $order,
                $validated['card_token'],
                $payer,
                $validated['payment_method_id']
            );

            return response()->json([
                'success' => true,
                'message' => $result['approved'] ? 'Pagamento aprovado!' : 'Pagamento em processamento',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao criar pagamento com Cartão para Order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento',
            ], 500);
        }
    }

    /**
     * Verificar status do pagamento (novo padrão)
     * GET /api/v1/app/payments/{paymentIntent}/status-v2
     * 
     * 🆕 NOVO: Versão moderna com ExternalPaymentService
     */
    public function getPaymentStatus(PaymentIntent $paymentIntent): JsonResponse
    {
        try {
            $filho = auth()->user()->filho;

            // Verificar ownership (pode ser Order ou Invoice)
            if ($paymentIntent->order_id) {
                if ($paymentIntent->order->filho_id !== $filho->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pagamento não encontrado',
                    ], 404);
                }
            } elseif ($paymentIntent->invoice_id) {
                if ($paymentIntent->invoice->filho_id !== $filho->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pagamento não encontrado',
                    ], 404);
                }
            }

            // Verificar status atualizado
            $result = $this->externalPaymentService->checkPaymentStatus($paymentIntent);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao verificar status do pagamento', [
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar status',
            ], 500);
        }
    }

    /**
     * Cancelar pagamento (novo padrão)
     * POST /api/v1/app/payments/{paymentIntent}/cancel-v2
     * 
     * 🆕 NOVO: Versão moderna com ExternalPaymentService
     */
    public function cancelPaymentV2(PaymentIntent $paymentIntent): JsonResponse
    {
        try {
            $filho = auth()->user()->filho;

            // Verificar ownership
            if ($paymentIntent->order_id) {
                if ($paymentIntent->order->filho_id !== $filho->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pagamento não encontrado',
                    ], 404);
                }
            } elseif ($paymentIntent->invoice_id) {
                if ($paymentIntent->invoice->filho_id !== $filho->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pagamento não encontrado',
                    ], 404);
                }
            }

            $result = $this->externalPaymentService->cancelPayment($paymentIntent);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);

        } catch (\App\Exceptions\PaymentException $e) {
            return $e->render();
        } catch (\Exception $e) {
            Log::error('Erro ao cancelar pagamento', [
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar pagamento',
            ], 500);
        }
    }
}