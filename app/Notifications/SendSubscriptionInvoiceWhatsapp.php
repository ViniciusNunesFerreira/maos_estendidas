<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Filho;
use App\Services\ZApiApiService;

class SendSubscriptionInvoiceWhatsapp extends Notification implements ShouldQueue
{
    use Queueable;

    protected $customMessage;

    public function __construct($customMessage = null)
    {
        $this->customMessage = $customMessage;
    }
    
    public function via(object $notifiable): array
    {
        return ['whatsapp'];
    }

    public function toWhatsapp(Filho $filho): array|false 
    {
        
        $phone = $filho->phone;

        // 1. Validar se o usuário tem número de WhatsApp.
        if (!$phone) {
            return false; 
        }

        \Log::info('mensagem enviado pro whatsapp: '.$this->customMessage);
        return [
            'to' => $phone,
            'message' => $this->customMessage, 
        ];


    }
   
}
