<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Filho;

class SendMessageWhatsApp extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */

    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['whatsapp'];
    }

    /**
     * Get the whatsapp representation of the notification.
     */
    public function toWhatsapp(Filho $filho): array|false 
    {
        // 1. Validar se o usuário tem número de WhatsApp.
        if (!$filho->phone) {
            return false; 
        }

        return [
            'to' => $filho->phone,
            'message' => $this->message, 
        ];

    }
}
