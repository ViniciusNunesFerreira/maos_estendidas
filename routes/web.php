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

/*
Route::get('/restaurar-saldo', function(){

    $filhos = Filho::where('status', 'active')->with(['orders'])->get();

    $orders = Order::where('type', 'consumption')
            ->whereNotNull('filho_id')
            ->whereNotNull('paid_at')
            ->where('is_invoiced', false)
            ->where('payment_method_chosen', '!=', 'carteira')
            ->with(['filho', 'paymentIntent'])
            ->get();

    foreach($orders as $order){
        if($order->paymentIntent->exists && $order->paymentIntent->status == 'approved'){
            $filho = $order->filho;
            $credit_used = $filho->credit_used;

            echo 'Filho:('.$filho->full_name.'), usou: R$ '.$credit_used.' de '.$filho->credit_limit.' e pagou: '.$order->total.' em: '.$order->payment_method_chosen;
            echo '<br>';

            $order->update( ['is_invoiced' => true, 'status' => 'paid' ] );
            
            if( $filho->credit_available < $filho->credit_limit ){
                echo '<br>disponivel: '.$filho->credit_available;
                echo '<br>';
                echo 'limite: '.$filho->credit_limit;
                echo '<br>';
                echo 'Usado: '.$filho->credit_used;
                $used = max(0, $filho->credit_used -= $order->total);
                echo 'Novo valor Usado: '.$used;
                $filho->update( ['credit_used' => $used ]);

            }
        
        }
    }


});
*/

Route::get('teste-service', function(){

  /* $service =  app(InvoiceService::class);

   $service->generateMonthlyInvoices();*/
    $today =  Carbon::today();
    $periodStart = $today->copy()->subMonth()->startOfMonth();

    // 2. Prevenção de duplicidade baseada em data (SARGable)
    $invoices = Invoice::whereNotNull('filho_id')
        ->where('type', 'consumption')
        ->where('is_avulse', false)
        ->where('period_start', $periodStart->format('Y-m-d'))
        ->get();

        foreach ($invoices as $invoice) {
                $filho= $invoice->filho;
                $filho->update(['credit_used' => 0 ]);
                \Log::info('Credito do filho: '.$filho->full_name.' renovado no fechamento do mês');
        }

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