<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResetPasswordOtpNotification extends Notification
{
    use Queueable;

    protected $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function via($notifiable): array
    {
        return ['whatsapp']; // Utiliza o canal definido no seu Provider
    }

    public function toWhatsapp($notifiable)
    {
        // Assume que o model User/Filho tem um método ou atributo getPhoneNumberAttribute
        // Remove formatação para garantir apenas números
        $phone = preg_replace('/\D/', '', $notifiable->phone);
        
        return [
            'to' => $phone,
            'message' => "🔐 *Mãos Estendidas*\n\nSeu código de recuperação de senha é: *{$this->code}*\n\nEste código expira em 10 minutos. Se não foi você quem solicitou, ignore esta mensagem."
        ];
    }
}