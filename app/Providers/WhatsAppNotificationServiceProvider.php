<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Notification as BaseNotification;
use App\Services\ZApiApiService;

class WhatsAppNotificationServiceProvider extends ServiceProvider
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
        Notification::extend('whatsapp', function ($app) {

           return new class($app->make(ZApiApiService::class)) {
                

                protected $zapiService;

                public function __construct(ZApiApiService $zapi)
                {
                    
                    $this->zapiService = $zapi;
                    
                }

                public function send(mixed $notifiable, BaseNotification $notification): void
                {
                    // Obtém os dados genéricos da notificação
                    $data = $notification->toWhatsapp($notifiable);
                    
                    if ($data === false) {
                        return; // Aborta
                    }

                  
                    $recipientNumber = $data['to'];

                    $this->zapiService->sendMessage($recipientNumber, $data['message']);
                        
                    
                }
            };
        });
    }
}
