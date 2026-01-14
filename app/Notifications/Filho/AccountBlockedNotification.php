<?php
// app/Notifications/Filho/AccountBlockedNotification.php

namespace App\Notifications\Filho;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountBlockedNotification extends Notification
{
    public function __construct(public readonly int $overdueCount) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'sms', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Conta Restrita - Mãos Estentidas')
            ->error()
            ->line('Sua conta foi temporariamente restrita devido a faturas em aberto.')
            ->line('Número de faturas vencidas: ' . $this->overdueCount)
            ->line('Para desbloquear sua conta, regularize suas faturas.')
            ->action('Ver Faturas Pendentes', route('filho.invoices.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'overdue_count' => $this->overdueCount,
        ];
    }
}