<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    protected $listen = [
        \App\Events\Invoice\InvoiceGenerated::class => [
            \App\Listeners\Invoice\SendInvoiceGeneratedNotification::class,
        ],
        \App\Events\Invoice\InvoicePaid::class => [
            \App\Listeners\Invoice\ProcessPaidInvoice::class,
        ],
        \App\Events\Order\OrderCreated::class => [
            \App\Listeners\Order\UpdateStockAfterOrder::class,
            \App\Listeners\Order\GenerateFiscalDocument::class,
        ],
    ];

    protected $observers = [
        \App\Models\Order::class => [\App\Observers\OrderObserver::class],
        \App\Models\Invoice::class => [\App\Observers\InvoiceObserver::class],
        \App\Models\Product::class => [\App\Observers\ProductObserver::class],
        \App\Models\Subscription::class => [\App\Observers\SubscriptionObserver::class],
    ];
    
}
