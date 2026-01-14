<?php
// app/Notifications/Filho/InvoiceDueNotification.php

namespace App\Notifications\Filho;

use App\Models\Invoice;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceDueNotification extends Notification
{
    public function __construct(public readonly Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'sms', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Lembrete: Fatura Próxima do Vencimento')
            ->line('Sua fatura está próxima do vencimento.')
            ->line('Valor: R$ ' . number_format($this->invoice->total_amount, 2, ',', '.'))
            ->line('Vencimento: ' . $this->invoice->due_date->format('d/m/Y'))
            ->action('Pagar Agora', route('filho.invoices.show', $this->invoice->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'amount' => $this->invoice->total_amount,
            'due_date' => $this->invoice->due_date,
        ];
    }
}