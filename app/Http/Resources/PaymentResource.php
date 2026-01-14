<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'method' => $this->method,
            'amount' => $this->amount,
            'change_amount' => $this->change_amount,
            'status' => $this->status,
            'gateway' => [
                'name' => $this->gateway_name,
                'transaction_id' => $this->gateway_transaction_id,
                'response' => $this->gateway_response,
            ],
            'card' => [
                'brand' => $this->card_brand,
                'last_digits' => $this->card_last_digits,
                'installments' => $this->installments,
            ],
            'pix' => [
                'key' => $this->pix_key,
                'qrcode' => $this->pix_qrcode,
            ],
            'dates' => [
                'authorized_at' => $this->authorized_at,
                'confirmed_at' => $this->confirmed_at,
                'failed_at' => $this->failed_at,
                'refunded_at' => $this->refunded_at,
            ],
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'order' => new OrderResource($this->whenLoaded('order')),
        ];
    }
}