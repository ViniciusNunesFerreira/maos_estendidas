<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\WelcomeController;
use App\Notifications\SendMessageWhatsApp;

use App\Models\Filho;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Web Routes - Casa Lar
|--------------------------------------------------------------------------
|
| Rotas web públicas do sistema
| Middleware: ['web']
|
*/

// =====================================================
// PÁGINA INICIAL
// =====================================================

Route::get('/', [WelcomeController::class, 'index'])->name('home');


// =====================================================
// AUTENTICAÇÃO (Guest)
// =====================================================

Route::middleware('guest')->group(function () {
    

        // Login com Rate Limiting (5 tentativas por minuto)
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:5,1'); // Proteção contra Brute Force
    
    
    // Esqueceu a senha
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    
    // Reset de senha
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// =====================================================
// LOGOUT (Authenticated)
// =====================================================

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');


// =====================================================
// HEALTH CHECK
// =====================================================

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'system' => 'Mãos Estendidas Backend',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('health');


Route::get('/manutencao-vincular-assinaturas', function () {
    // 1. Verificação de segurança: Apenas usuários autenticados ou ambiente local
    // if (app()->environment('production') && !auth()->check()) abort(403);

    $report = [
        'total_filhos_processados' => 0,
        'invoices_vinculadas' => 0,
        'erros' => []
    ];

    try {
        DB::transaction(function () use (&$report) {
            // Buscamos apenas filhos que possuem uma assinatura ativa
            Filho::where('status', 'active')
                ->whereHas('subscription', function($q) {
                    $q->where('status', 'active');
                })
                ->chunk(100, function ($filhos) use (&$report) {
                    foreach ($filhos as $filho) {
                        $report['total_filhos_processados']++;

                        // Pegamos a assinatura ativa atual do filho
                        $subscription = $filho->subscription; 

                        if ($subscription) {
                            // Buscamos invoices do tipo 'subscription' deste filho que estão sem subscription_id
                            $updatedCount = Invoice::where('filho_id', $filho->id)
                                ->where('type', 'subscription')
                                ->whereNull('subscription_id')
                                ->update(['subscription_id' => $subscription->id]);

                            $report['invoices_vinculadas'] += $updatedCount;
                        }
                    }
                });
        });

        return response()->json([
            'message' => 'Manutenção concluída com sucesso!',
            'dados' => $report
        ]);

    } catch (\Exception $e) {
        Log::error("Erro na manutenção de assinaturas: " . $e->getMessage());
        return response()->json([
            'message' => 'Erro durante o processo',
            'error' => $e->getMessage()
        ], 500);
    }
});