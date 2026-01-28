<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\App\ProfileController;
use App\Http\Controllers\Api\V1\App\InvoiceController as AppInvoiceController;
use App\Http\Controllers\Api\V1\App\SubscriptionController as AppSubscriptionController;
use App\Http\Controllers\Api\V1\App\MenuController as AppMenuController;
use App\Http\Controllers\Api\V1\App\OrderController as AppOrderController;
use App\Http\Controllers\Api\V1\App\BalanceController as AppBalanceController;
use App\Http\Controllers\Api\V1\App\PaymentController;
use App\Http\Controllers\Api\V1\App\TransactionController;
use App\Http\Controllers\Api\V1\PDV\FilhoController as PDVFilhoController;
use App\Http\Controllers\Api\V1\PDV\ProductController as PDVProductController;
use App\Http\Controllers\Api\V1\PDV\OrderController as PDVOrderController;
use App\Http\Controllers\Api\V1\PDV\PaymentController as PDVPaymentController;
use App\Http\Controllers\Api\V1\PDV\SyncController as PDVSyncController;
use App\Http\Controllers\Api\V1\Totem\MenuController as TotemMenuController;
use App\Http\Controllers\Api\V1\Totem\OrderController as TotemOrderController;
use App\Http\Controllers\Api\V1\Totem\PaymentController as TotemPaymentController;

use App\Http\Controllers\Api\V1\Webhook\PaymentController as WebhookPaymentController;
use App\Http\Controllers\Api\V1\Webhook\SmsController as WebhookSmsController;
use App\Http\Controllers\Api\V1\Webhook\MercadoPagoWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes - Casa Lar v1
|--------------------------------------------------------------------------
|
| Rotas da API REST do sistema Casa Lar
| Versão: 1.0
| Autenticação: Laravel Sanctum
| Rate Limiting: 60 requisições/minuto (autenticado), 20/min (público)
|
*/

Route::prefix('v1')->group(function () {

    // =========================================================
    // ROTAS PÚBLICAS (Sem Autenticação)
    // =========================================================
    
    Route::prefix('auth')->group(function () {
        
        // Login padrão (Admin/Operadores/Staff)
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1');
        
        // Login via CPF (App/Totem dos Filhos)
        Route::post('login-cpf', [AuthController::class, 'loginByCpf'])
            ->middleware('throttle:5,1');

        Route::post('register', [AuthController::class, 'register']);
        
        // Recuperação de senha via SMS
        Route::post('password/request-sms', [PasswordResetController::class, 'requestSms'])
            ->middleware('throttle:3,10');
        
        Route::post('password/verify-code', [PasswordResetController::class, 'verifyCode'])
            ->middleware('throttle:5,10');
        
        Route::post('password/reset-sms', [PasswordResetController::class, 'resetWithSms'])
            ->middleware('throttle:3,10');
    });

    // =========================================================
    // WEBHOOKS MERCADO PAGO (Sem autenticação)
    // =========================================================

    Route::prefix('webhooks')->group(function () {
        Route::post('/mercadopago', [MercadoPagoWebhookController::class, 'handle'])
            ->middleware('verify.mercadopago.signature')
            ->name('api.webhooks.mercadopago');
        
        // Gateway de pagamento
        Route::post('/payment/notification', [WebhookPaymentController::class, 'notification'])
            ->middleware('verify.payment.signature');
        
        // Twilio SMS
        Route::post('/sms/status', [WebhookSmsController::class, 'status'])
            ->middleware('verify.twilio.signature');
    });

    // =========================================================
    // ROTAS AUTENTICADAS (Sanctum)
    // =========================================================
    
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth - Logout e perfil básico
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::post('refresh', [AuthController::class, 'refresh']);
        });

        // =====================================================
        // APP DOS FILHOS (ability:filho:*)
        // =====================================================
        
        Route::prefix('app')
            ->middleware('ability:filho:*')
            ->group(function () {
            
            // Perfil
            Route::prefix('profile')->group(function () {
                Route::get('/', [ProfileController::class, 'show']);
                Route::put('/', [ProfileController::class, 'update']);
                Route::put('/password', [ProfileController::class, 'updatePassword']);
                Route::post('/photo', [ProfileController::class, 'uploadPhoto']);
            });
            
            // Saldo e Crédito
            Route::prefix('balance')->group(function () {
                Route::get('/', [AppBalanceController::class, 'show']);
                Route::get('/transactions', [AppBalanceController::class, 'transactions']);
            });
            
            // Faturas de Consumo
            Route::prefix('invoices')->group(function () {
                Route::get('/summary', [AppInvoiceController::class, 'summary']);
    
                // Faturas de Consumo
                Route::get('/consumption', [AppInvoiceController::class, 'consumption']);
                Route::get('/consumption/{invoice}', [AppInvoiceController::class, 'showConsumption']);
                
                // Faturas de Assinatura
                Route::get('/subscription', [AppInvoiceController::class, 'subscription']);
                Route::get('/subscription/{invoice}', [AppInvoiceController::class, 'showSubscription']);
                
                // Ações
                Route::post('/{invoice}/payment-link', [AppInvoiceController::class, 'generatePaymentLink']);
                Route::post('/{invoice}/confirm-payment', [AppInvoiceController::class, 'confirmPayment']);
                Route::get('/{invoice}/download-pdf', [AppInvoiceController::class, 'downloadPDF']);
                Route::post('/{invoice}/share', [AppInvoiceController::class, 'shareInvoice']);
                Route::post('/{invoice}/cancel', [AppInvoiceController::class, 'cancel']);
            });
            
            // Assinatura
            Route::prefix('subscription')->group(function () {
                Route::get('/', [AppSubscriptionController::class, 'show']);
                Route::get('/history', [AppSubscriptionController::class, 'history']);
            });
            
            // Menu de Produtos (Cantina filtrada)
            Route::prefix('menu')->group(function () {
                Route::get('/', [AppMenuController::class, 'index']);
                Route::get('/categories', [AppMenuController::class, 'categories']);
                Route::get('/products/{product}', [AppMenuController::class, 'show']);
            });
            
            // ===================================================
            // PEDIDOS - NOVO FLUXO
            // ===================================================
            Route::prefix('orders')->group(function () {
                // Listagem e estatísticas (SEM parâmetros - vêm PRIMEIRO)
                Route::get('/', [AppOrderController::class, 'index']);
                Route::get('/active', [AppOrderController::class, 'active']);
                Route::get('/stats', [AppOrderController::class, 'stats']);
                
                // Criar pedido (SEM pagamento)
                Route::post('/', [AppOrderController::class, 'store']);
                
                // Ações específicas (COM parâmetro {order} - vêm DEPOIS)
                Route::get('/{order}', [AppOrderController::class, 'show']);
                Route::get('/{order}/track', [AppOrderController::class, 'track']);
                Route::post('/{order}/cancel', [AppOrderController::class, 'cancel']);
                Route::post('/{order}/repeat', [AppOrderController::class, 'repeat']);
                
                // 🆕 PROCESSAR PAGAMENTO - NOVO ENDPOINT
                Route::post('/{order}/pay', [AppOrderController::class, 'pay']);
            });

            Route::prefix('transactions')->group(function(){
                // Transações
                Route::get('/', [TransactionController::class, 'index']);
                Route::post('/consume-limit', [TransactionController::class, 'consumeLimit']);
                Route::get('/balance', [TransactionController::class, 'getBalance']);
            });

            // Payments - Mercado Pago (Mantido para retrocompatibilidade)
            Route::prefix('payments')->group(function () {
                Route::post('/create-pix', [PaymentController::class, 'createPixPayment']);
                Route::post('/create-card', [PaymentController::class, 'createCardPayment']);
                
                Route::post('/{paymentIntent}/cancel', [PaymentController::class, 'cancelPayment']);

                // ========== NOVOS ENDPOINTS - ORDERS (MODERNOS) ==========
                Route::post('/orders/{order}/pix', [PaymentController::class, 'createOrderPix']);
                Route::post('/orders/{order}/card', [PaymentController::class, 'createOrderCard']);
                
                // ========== NOVOS ENDPOINTS - INVOICES ==========
                Route::post('/invoices/{invoice}/pix', [PaymentController::class, 'createInvoicePixPayment']);
                Route::post('/invoices/{invoice}/card', [PaymentController::class, 'createInvoiceCardPayment']);
                Route::get('/invoices/pending', [PaymentController::class, 'getPendingInvoices']);
                
                // ========== NOVOS ENDPOINTS - STATUS/CANCEL (VERSÕES MODERNAS) ==========
                Route::get('/{paymentIntent}/status-v2', [PaymentController::class, 'checkStatus']);
                Route::post('/{paymentIntent}/cancel-v2', [PaymentController::class, 'cancelPaymentV2']);

            });
            
        });

        // =====================================================
        // PDV DESKTOP (ability:pdv:*)
        // =====================================================
        
        Route::prefix('pdv')
            ->middleware('ability:pdv:*')
            ->group(function () {
            
            // Busca de Filhos
            Route::prefix('filhos')->group(function () {
                Route::get('/search', [PDVFilhoController::class, 'search']);
                Route::get('/{cpf}', [PDVFilhoController::class, 'getByCpf']);
                Route::get('/{cpf}/balance', [PDVFilhoController::class, 'getBalance']);
            });
            
            // Produtos
            Route::prefix('products')->group(function () {
                Route::get('/', [PDVProductController::class, 'index']);
                Route::get('/search', [PDVProductController::class, 'search']);
                Route::get('/{product}', [PDVProductController::class, 'show']);
                Route::get('/barcode/{barcode}', [PDVProductController::class, 'getByBarcode']);
            });
            
            // Categorias
            Route::get('/categories', [PDVProductController::class, 'categories']);
            
            // Pedidos
            Route::prefix('orders')->group(function () {
                Route::get('/', [PDVOrderController::class, 'index']);
                Route::post('/', [PDVOrderController::class, 'store']);
                Route::get('/{order}', [PDVOrderController::class, 'show']);
                Route::put('/{order}', [PDVOrderController::class, 'update']);
                Route::post('/{order}/cancel', [PDVOrderController::class, 'cancel']);
            });
            
            // Pagamentos
            Route::prefix('payments')->group(function () {
                Route::post('/process', [PDVPaymentController::class, 'process']);
                Route::post('/refund', [PDVPaymentController::class, 'refund']);
            });
            
            // Sincronização Offline
            Route::prefix('sync')->group(function () {
                Route::post('/batch', [PDVSyncController::class, 'batchSync']);
                Route::get('/pending', [PDVSyncController::class, 'pending']);
                Route::post('/resolve-conflicts', [PDVSyncController::class, 'resolveConflicts']);
            });
        });

        // =====================================================
        // TOTEM DE AUTOATENDIMENTO (ability:totem:*)
        // =====================================================
        
        Route::prefix('totem')
            ->middleware('ability:totem:*')
            ->group(function () {
            
            // Login específico do totem (CPF + Senha)
            Route::post('login-cpf', [AuthController::class, 'loginByCpf'])
                ->withoutMiddleware('auth:sanctum');
            
            // Menu (apenas produtos da cantina)
            Route::prefix('menu')->group(function () {
                Route::get('/', [TotemMenuController::class, 'index']);
                Route::get('/categories', [TotemMenuController::class, 'categories']);
                Route::get('/products/{product}', [TotemMenuController::class, 'show']);
            });
            
            // Pedidos
            Route::prefix('orders')->group(function () {
                Route::post('/', [TotemOrderController::class, 'store']);
                Route::get('/{order}', [TotemOrderController::class, 'show']);
                Route::get('/{order}/status', [TotemOrderController::class, 'status']);
                Route::post('/{order}/cancel', [TotemOrderController::class, 'cancel']);
            });
            
            // Pagamento
            Route::post('/payments/process', [TotemPaymentController::class, 'process']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '1.0.0'),
    ]);
});

/*
|--------------------------------------------------------------------------
| Fallback (404)
|--------------------------------------------------------------------------
*/

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint não encontrado',
        'error_code' => 'ROUTE_NOT_FOUND',
    ], 404);
});

require __DIR__.'/pdv_auth.php';