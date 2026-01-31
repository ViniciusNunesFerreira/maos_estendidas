<?php

namespace App\Providers;


use App\Models\Invoice;
use App\Models\Order;
use App\Models\Filho;
use App\Models\Product;
use App\Models\Subscription;

use App\Observers\InvoiceObserver;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use App\Observers\SubscriptionObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Validator::extend('cpf', function ($attribute, $value, $parameters, $validator) {
            // Remove caracteres não numéricos
            $cpf = preg_replace('/[^0-9]/', '', $value);

            if (strlen($cpf) != 11) return false;

            // Verifica se todos os dígitos são iguais (ex: 111.111.111-11)
            if (preg_match('/(\d)\1{10}/', $cpf)) return false;

            // Cálculo dos dígitos verificadores
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf[$c] != $d) return false;
            }
            return true;
        });

        if (!app()->runningInConsole()) {
        
            if (Schema::hasTable('filhos')) { 
                // Registrar Observers
                View::share('pendingApprovals', Filho::where('status', 'inactive')->count());
            }

        }

        // Limite para envio de OTP (3 por hora por CPF)
        RateLimiter::for('password-otp', function (Request $request) {
            return Limit::perHour(5)->by($request->cpf ?: $request->ip());
        });

        // Limite para a tentativa de redefinição final (evitar brute force na senha)
        RateLimiter::for('password-reset-final', function (Request $request) {
            return Limit::perMinute(5)->by($request->cpf ?: $request->ip());
        });

       // Invoice::observe(InvoiceObserver::class);
       // Order::observe(OrderObserver::class);
        Product::observe(ProductObserver::class);
        Subscription::observe(SubscriptionObserver::class);
    }
}
