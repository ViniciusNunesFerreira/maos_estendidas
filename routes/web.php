<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\WelcomeController;
use App\Notifications\SendMessageWhatsApp;
 use Illuminate\Support\Facades\Storage;

use App\Models\Filho;


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
   
    // Listar arquivos na raiz do disco 'local'
    $files = Storage::files('pdv-install');

    // Listar arquivos recursivamente (incluindo subpastas)
    $allFiles = Storage::allFiles('pdv-install');

    foreach ($files as $file) {
        echo $file; // Exibe o caminho relativo
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