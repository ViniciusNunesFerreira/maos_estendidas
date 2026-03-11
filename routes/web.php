<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\WelcomeController;
use App\Notifications\SendMessageWhatsApp;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use App\Services\InvoiceService;
use App\Jobs\ProcessInvoiceNotificationJob;

use App\Models\Filho;
use App\Models\Order;
use App\Models\Invoice;
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

Route::get('/pdv/updates', function(){
    echo "<br>";
    echo "Arquivos do PDV para atualização:\n". "<br>"; 
    echo "<hr>";

    // Listar arquivos na raiz do disco 'local'
    $files = File::files(public_path('pdv-install'));

    foreach ($files as $file) {
       $last_changed = date('d/m/Y H:i:s', $file->getCTime()); 

        echo("========================================= <br>");
        echo "<strong>".$file->getFilename(). "</strong> | Criado em:". $last_changed ."<br>";
       
        // Nome do arquivo
        echo "<a href='".url('pdv-install/'.$file->getFilename())."'> Download : ". $file->getFilename()." </a> <br>"; // Caminho completo
        echo("========================================= <br>");
    }

});

Route::get('/admin/reconcile-filhos-credit', function () {
    // 1. Buscamos todos os filhos que possuem algum crédito em uso
    $filhoIds = Filho::where('credit_used', '>', 0)->pluck('id');

    $report = [
        'analyzed' => 0,
        'fixed' => 0,
        'total_restored' => 0,
        'details' => []
    ];

    
        foreach ($filhoIds as $id) {

            try {

                DB::transaction(function () use ($id, &$report) {

                    $filho = Filho::where('id', $id)->lockForUpdate()->first();

                    if (!$filho) return;

                    $report['analyzed']++;
                    
                    $realDebt = Order::where('filho_id', $filho->id)
                        ->where('customer_type', 'filho')
                        ->where('status', 'delivered')
                        ->where('is_invoiced', false)
                        ->whereNull('invoice_id')
                        ->where('payment_method_chosen', 'carteira')
                        ->sum('total');

                    /**
                     * 3. COMPARAÇÃO DE SEGURANÇA
                     * Se o credit_used no banco for maior que a dívida real calculada,
                     * significa que há um "fantasma" de saldo preso.
                     */
                    if ($filho->credit_used > ($realDebt + 0.01)) { // Margem de 1 centavo para floats
                        $difference = $filho->credit_used - $realDebt;

                        $report['fixed']++;
                        $report['total_restored'] += $difference;
                        $report['details'][] = [
                            'filho' => $filho->full_name,
                            'id' => $filho->id,
                            'saldo_no_banco' => $filho->credit_used,
                            'divida_real_calculada' => $realDebt,
                            'ajuste_realizado' => $difference
                        ];

                        // Ajusta o saldo para bater exatamente com a dívida real
                         $filho->update([
                            'credit_used' => $realDebt,
                            'notes' => $filho->notes . "\n[SISTEMA] Saldo recalculado em " . now()->format('d/m/Y') . ". Diferença de {$difference} removida."
                        ]);

                        Log::info("RECONCILIAÇÃO: Filho #{$filho->full_name} corrigido. Banco: {$filho->credit_used}, Real: {$realDebt}");
                    }

                });
                
            } catch (\Exception $e) {
                $report['errors'][] = "Erro no Filho #{$id}: " . $e->getMessage();
                Log::error("RECONCILIAÇÃO FALHA: Filho #{$id}", ['error' => $e->getMessage()]);
            }

        }
  

    return response()->json($report);
});



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

Route::get('/admin/orders/{order}/print', function (Order $order) {
    return view('admin.orders.print', compact('order'));
})->name('admin.orders.print')->middleware('auth');

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