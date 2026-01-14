<?php
// app/Notifications/Admin/LowStockAlertNotification.php

namespace App\Notifications\Admin;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlertNotification extends Notification
{
    public function __construct(public readonly Collection $products) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Alerta: Produtos com Estoque Baixo')
            ->line('Os seguintes produtos estão com estoque baixo:');

        foreach ($this->products as $product) {
            $message->line('- ' . $product->name . ': ' . $product->stock_quantity . ' unidades');
        }

        return $message->action('Ver Estoque', route('admin.stock.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'products' => $this->products->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'stock' => $p->stock_quantity
            ])->toArray()
        ];
    }
}