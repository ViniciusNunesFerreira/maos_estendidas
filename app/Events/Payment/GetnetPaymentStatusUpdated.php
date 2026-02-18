<?php

namespace App\Events\Payment;

use App\Models\GetnetTransaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado quando status de pagamento Getnet é atualizado
 * 
 * Broadcasting via WebSocket para notificar PDV em tempo real
 */
class GetnetPaymentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GetnetTransaction $transaction;

    /**
     * Create a new event instance.
     */
    public function __construct(GetnetTransaction $transaction)
    {
        $this->transaction = $transaction->load(['order', 'paymentIntent']);
    }

    /**
     * Get the channels the event should broadcast on.
     * 
     * Canal específico do PDV device para garantir que apenas 
     * o terminal correto receba a notificação
     */
    public function broadcastOn(): Channel
    {
        // Canal específico do device que criou a transação
        return new Channel("pdv-device.{$this->transaction->pdv_device_id}");
    }

    /**
     * Nome do evento no WebSocket
     */
    public function broadcastAs(): string
    {
        return 'getnet.payment.status.updated';
    }

    /**
     * Dados transmitidos via WebSocket
     */
    public function broadcastWith(): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'payment_id' => $this->transaction->payment_id,
            'order_id' => $this->transaction->order_id,
            'order_number' => $this->transaction->order->order_number,
            'status' => $this->transaction->status,
            'status_message' => $this->transaction->status_message,
            'payment_method' => $this->transaction->payment_method,
            'amount' => $this->transaction->amount,
            'is_approved' => $this->transaction->is_approved,
            'is_denied' => $this->transaction->is_denied,
            'is_pending' => $this->transaction->is_pending,
            'is_finalized' => $this->transaction->is_finalized,
            
            // Dados de autorização (se aprovado)
            'nsu' => $this->transaction->nsu,
            'authorization_code' => $this->transaction->authorization_code,
            'card_brand' => $this->transaction->card_brand,
            'card_last_digits' => $this->transaction->card_last_digits,
            
            // Dados PIX (se aplicável)
            'pix_qr_code' => $this->transaction->pix_qr_code,
            'pix_qr_code_base64' => $this->transaction->pix_qr_code_base64,
            
            // Metadados úteis
            'denial_reason' => $this->transaction->denial_reason,
            'elapsed_time' => $this->transaction->elapsed_time,
            'created_at' => $this->transaction->created_at->toIso8601String(),
            'updated_at' => $this->transaction->updated_at->toIso8601String(),
            
            // Timestamp para sincronização
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Determina se o evento deve ser broadcast
     */
    public function broadcastWhen(): bool
    {
        // Só faz broadcast se houver device_id (terminal origem)
        return !empty($this->transaction->pdv_device_id);
    }
}