<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'auth', 'admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'broadcasting/auth'
        ]);
        
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminAuth::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'check.filho.status' => \App\Http\Middleware\CheckFilhoStatus::class,
            'check.credit.limit' => \App\Http\Middleware\CheckCreditLimit::class,
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'verify.mercadopago.signature' => \App\Http\Middleware\VerifyMercadoPagoSignature::class,
            'check.store' => \App\Http\Middleware\CheckStoreStatus::class,
            'check.cash.session' => \App\Http\Middleware\EnsureCashSessionIsOpen::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
