<?php

namespace App\Events;

use App\Models\PaymentIntent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento de Pagamento Aprovado/Atualizado
 * * Responsável por notificar clientes (PWA, PDV, Totem) em tempo real
 * sobre a mudança de status de um pagamento via WebSocket (Reverb).
 */
class PaymentApproved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PaymentIntent $paymentIntent
    ) {}

    /**
     * Get the channels the event should broadcast on.
     * * Define o canal privado seguro: payment.{uuid}
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('payment.' . $this->paymentIntent->id),
        ];
    }

    /**
     * The event's broadcast name.
     * * Define um alias para o evento no Frontend.
     * Isso desacopla o nome da classe PHP do Javascript.
     * No Frontend: .listen('.PaymentStatusUpdated')
     */
    public function broadcastAs(): string
    {
        return 'PaymentStatusUpdated';
    }

    /**
     * Get the data to broadcast.
     * * Filtra estritamente os dados enviados para o WebSocket.
     * Evita vazamento de dados sensíveis do Model.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->paymentIntent->id,
            'status' => $this->paymentIntent->status, // 'approved', 'rejected', etc.
            'approved' => $this->paymentIntent->status === 'approved',
            'order_id' => $this->paymentIntent->order_id,
            'invoice_id' => $this->paymentIntent->invoice_id, // Suporte a Faturas (App)
            'mp_payment_id' => $this->paymentIntent->mp_payment_id, // Útil para conciliação no PDV
            'amount_paid' => (float) $this->paymentIntent->amount_paid,
            'processed_at' => now()->toIso8601String(),
            'metadata' => [
                'origin' => $this->paymentIntent->integration_type ?? 'checkout', // checkout, point_tef
                'method' => $this->paymentIntent->payment_method // pix, credit_card
            ]
        ];
    }
}