<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Filho;
use App\Services\ZApiApiService;

class SendMessageWhatsApp extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */

    protected $customMessage;

    public function __construct($customMessage = null)
    {
        $this->customMessage = $customMessage;
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
        $zapi = app(ZApiApiService::class);
        $phone = $filho->phone;

        // 1. Validar se o usuário tem número de WhatsApp.
        if (!$phone) {
            return false; 
        }

        // 1. Simula "Digitando..." por um tempo aleatório
        $zapi->sendPresence($phone, 'composing');

        // Delay humano: Espera entre 3 a 6 segundos simulando a escrita
        sleep(rand(5, 8));

        // 2. Variabilidade de Saudação (Evita o padrão de Spam)
        $saudacoes = ['Olá', 'Oi', 'Tudo bem?', 'E aí!', 'Oi Filho!'];
        $saudacao = $saudacoes[array_rand($saudacoes)];
        
        $msgFinal = $this->customMessage ?? "{$saudacao} Seja bem-vindo(a)! Recebemos seu cadastro e já estamos analisando. Logo te avisamos!";

        return [
            'to' => $filho->phone,
            'message' => $msgFinal, 
        ];

    }
}
