<?php
// app/Notifications/Filho/InvoiceGeneratedNotification.php

namespace App\Notifications\Filho;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceGeneratedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nova Fatura Gerada - Mãos Estendidas')
            ->line('Uma nova fatura foi gerada para você.')
            ->line('Período: ' . $this->invoice->reference_month . '/' . $this->invoice->reference_year)
            ->line('Valor: R$ ' . number_format($this->invoice->total_amount, 2, ',', '.'))
            ->line('Vencimento: ' . $this->invoice->due_date->format('d/m/Y'))
            ->action('Ver Fatura', route('filho.invoices.show', $this->invoice->id))
            ->line('Obrigado por utilizar nossos serviços!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'amount' => $this->invoice->total_amount,
            'due_date' => $this->invoice->due_date,
            'reference_month' => $this->invoice->reference_month,
            'reference_year' => $this->invoice->reference_year,
        ];
    }
}