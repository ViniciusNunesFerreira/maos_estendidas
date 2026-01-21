<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FilhoController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingsController;

use App\Livewire\Admin\Filhos\FilhoSubscriptionForm;

use App\Http\Controllers\Admin\PaymentSettingsController;

/*
|--------------------------------------------------------------------------
| Admin Routes - Mãos Estendidas
|--------------------------------------------------------------------------
|
| Rotas do painel administrativo
| Middleware: web, auth, admin
| Prefix: /admin
| Name: admin.*
|
*/

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Filhos
Route::prefix('filhos')->name('filhos.')->group(function () {
    Route::get('/', [FilhoController::class, 'index'])->name('index');
    Route::get('/create', [FilhoController::class, 'create'])->name('create');
    //Route::post('/', [FilhoController::class, 'store'])->name('store');
    Route::get('/approval', [FilhoController::class, 'approvalQueue'])->name('approval');
    Route::get('/{filho}', [FilhoController::class, 'show'])->name('show');
    Route::get('/{filho}/edit', [FilhoController::class, 'edit'])->name('edit');
   // Route::put('/{filho}', [FilhoController::class, 'update'])->name('update');
    Route::delete('/{filho}', [FilhoController::class, 'destroy'])->name('destroy');
   // Route::post('/{filho}/approve', [FilhoController::class, 'approve'])->name('approve');
    Route::post('/{filho}/reject', [FilhoController::class, 'reject'])->name('reject');
    Route::post('/{filho}/toggle-status', [FilhoController::class, 'toggleStatus'])->name('toggle-status');
    Route::post('/{filho}/credit', [FilhoController::class, 'adjustCredit'])->name('credit');
    Route::get('/{filho}/invoices', [FilhoController::class, 'invoices'])->name('invoices');
    Route::get('/{filho}/orders', [FilhoController::class, 'orders'])->name('orders');
});

// Produtos
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/create', [ProductController::class, 'create'])->name('create');
    Route::post('/', [ProductController::class, 'store'])->name('store');
    Route::get('/{product}', [ProductController::class, 'show'])->name('show');
    Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
    Route::put('/{product}', [ProductController::class, 'update'])->name('update');
    Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
    Route::post('/import', [ProductController::class, 'import'])->name('import');
    Route::get('/export', [ProductController::class, 'export'])->name('export');
});

// Categorias
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/create', [CategoryController::class, 'create'])->name('create');
    Route::post('/', [CategoryController::class, 'store'])->name('store');
    Route::get('/{category}', [CategoryController::class, 'show'])->name('show');
    Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
    Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
    Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
});

// Estoque
Route::prefix('stock')->name('stock.')->group(function () {
    Route::get('/', [StockController::class, 'index'])->name('index');
    Route::get('/entry', [StockController::class, 'entry'])->name('entry');
    Route::post('/entry', [StockController::class, 'storeEntry'])->name('store-entry');
    Route::get('/out', [StockController::class, 'out'])->name('out');
    Route::post('/out', [StockController::class, 'storeOut'])->name('store-out');
    Route::get('/movements', [StockController::class, 'movements'])->name('movements');
});

// Pedidos
Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
    Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])->name('update-status');
    Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
    Route::get('/{order}/print', [OrderController::class, 'print'])->name('print');
});

// Faturas
Route::prefix('invoices')->name('invoices.')->group(function () {
    Route::get('/', [InvoiceController::class, 'index'])->name('index');
    Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
    Route::post('/{invoice}/payment', [InvoiceController::class, 'registerPayment'])->name('payment');
    Route::post('/{invoice}/reminder', [InvoiceController::class, 'sendReminder'])->name('reminder');
    Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('cancel');
});

// Assinaturas
Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
    Route::get('/', [SubscriptionController::class, 'index'])->name('index');
    Route::get('/create', [SubscriptionController::class, 'create'])->name('create');
    Route::post('/', [SubscriptionController::class, 'store'])->name('store');
    Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('show');
    Route::get('/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('edit');
    Route::put('/{subscription}', [SubscriptionController::class, 'update'])->name('update');
    Route::post('/{subscription}/pause', [SubscriptionController::class, 'pause'])->name('pause');
    Route::post('/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('resume');
    Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
    Route::post('/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('renew');
});

// Relatórios
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
    Route::get('/products', [ReportController::class, 'products'])->name('products');
    Route::get('/financial', [ReportController::class, 'financial'])->name('financial');
    Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
    Route::post('/export', [ReportController::class, 'export'])->name('export');
});

// Usuários (apenas admin)
Route::middleware('role:admin')->prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/create', [UserController::class, 'create'])->name('create');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('/{user}', [UserController::class, 'show'])->name('show');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
    Route::put('/{user}', [UserController::class, 'update'])->name('update');
    Route::patch('/{user}/password', [UserController::class, 'updatePassword'])->name('update-password');
    Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
});

// Configurações (apenas admin)
Route::middleware('role:admin')->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::post('/general', [SettingsController::class, 'updateGeneral'])->name('general');
    Route::post('/subscription', [SettingsController::class, 'updateSubscription'])->name('subscription');
    Route::post('/fiscal', [SettingsController::class, 'updateFiscal'])->name('fiscal');
});

// =========================================================
// CONFIGURAÇÕES DE PAGAMENTO (Admin)
// =========================================================

Route::middleware('role:admin')->prefix('settings')->name('settings.')->group(function () {
    
    Route::get('/payment-gateways', [PaymentSettingsController::class, 'index'])
        ->name('payment-gateways');
    
    Route::post('/payment-gateways/credentials', [PaymentSettingsController::class, 'updateCredentials'])
        ->name('payment-gateways.credentials');
    
    Route::post('/payment-gateways/point', [PaymentSettingsController::class, 'updatePoint'])
        ->name('payment-gateways.point');
    
    Route::post('/payment-gateways/methods', [PaymentSettingsController::class, 'updateMethods'])
        ->name('payment-gateways.methods');
    
    Route::post('/payment-gateways/toggle', [PaymentSettingsController::class, 'toggle'])
        ->name('payment-gateways.toggle');
    
    Route::post('/payment-gateways/test', [PaymentSettingsController::class, 'testConnection'])
        ->name('payment-gateways.test');
    
    // Devices Point
    Route::post('/payment-gateways/devices', [PaymentSettingsController::class, 'storeDevice'])
        ->name('payment-gateways.devices.store');
    
    Route::put('/payment-gateways/devices/{device}', [PaymentSettingsController::class, 'updateDevice'])
        ->name('payment-gateways.devices.update');
    
    Route::post('/payment-gateways/devices/{device}/toggle', [PaymentSettingsController::class, 'toggleDevice'])
        ->name('payment-gateways.devices.toggle');
    
    Route::delete('/payment-gateways/devices/{device}', [PaymentSettingsController::class, 'destroyDevice'])
        ->name('payment-gateways.devices.destroy');
});