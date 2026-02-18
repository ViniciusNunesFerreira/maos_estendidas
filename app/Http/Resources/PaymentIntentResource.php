<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para PaymentIntent
 * Formata resposta da API de forma consistente
 */
class PaymentIntentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            // Identificação
            'id' => $this->id,
            'order_id' => $this->order_id,
            'invoice_id' => $this->invoice_id,
            'payment_id' => $this->payment_id,

            // Mercado Pago IDs
            'mp_payment_id' => $this->mp_payment_id,
            'mp_payment_intent_id' => $this->mp_payment_intent_id,

            // Tipo de integração e método
            'integration_type' => $this->integration_type, // checkout, point_tef, manual_pos
            'payment_method' => $this->payment_method, // pix, credit_card, debit_card

            // Valores
            'amount' => (float) $this->amount,
            'amount_paid' => (float) $this->amount_paid,

            // Status
            'status' => $this->status,
            'status_detail' => $this->status_detail,
            'status_message' => $this->getStatusMessage(),

            // Flags booleanas
            'is_pix' => $this->is_pix,
            'is_card' => $this->is_card,
            'is_tef' => $this->is_tef,
            'is_pending' => $this->is_pending,
            'is_approved' => $this->is_approved,
            'is_rejected' => $this->is_rejected,
            'is_cancelled' => $this->is_cancelled,
            'has_error' => $this->has_error,

            // Dados PIX (quando aplicável)
            'pix' => $this->when($this->is_pix, function () {
                return [
                    'qr_code' => $this->pix_qr_code,
                    'qr_code_base64' => $this->pix_qr_code_base64,
                    'ticket_url' => $this->pix_ticket_url,
                    'expiration' => $this->pix_expiration?->toIso8601String(),
                    'is_expired' => $this->isPixExpired(),
                    'expires_in_seconds' => $this->pix_expiration 
                        ? max(0, now()->diffInSeconds($this->pix_expiration, false))
                        : null,
                ];
            }),

            // Dados de Cartão (quando aplicável)
            'card' => $this->when($this->is_card, function () {
                return [
                    'last_digits' => $this->card_last_digits,
                    'brand' => $this->card_brand,
                    'installments' => $this->installments,
                ];
            }),

            // Dados TEF (quando aplicável)
            'tef' => $this->when($this->is_tef, function () {
                return [
                    'device_id' => $this->tef_device_id,
                    'external_reference' => $this->tef_external_reference,
                    'print_on_terminal' => $this->tef_print_on_terminal,
                ];
            }),

            // Metadados de tentativas
            'attempts' => $this->attempts,
            'last_attempt_at' => $this->last_attempt_at?->toIso8601String(),
            'last_error' => $this->last_error,

            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'created_at_mp' => $this->created_at_mp?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Dados do pedido (simplificados)
            'order' => $this->when($this->relationLoaded('order'), function () {
                return [
                    'id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'total' => (float) $this->order->total,
                    'status' => $this->order->status,
                ];
            }),

            // Dados do pagamento (quando existe)
            'payment' => $this->when($this->relationLoaded('payment'), function () {
                return [
                    'id' => $this->payment->id,
                    'method' => $this->payment->method,
                    'amount' => (float) $this->payment->amount,
                    'status' => $this->payment->status,
                    'confirmed_at' => $this->payment->confirmed_at?->toIso8601String(),
                ];
            }),
        ];
    }
}