<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'type' => $this->type,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'period' => [
                'start' => $this->period_start,
                'end' => $this->period_end,
            ],
            'dates' => [
                'issue_date' => $this->issue_date,
                'due_date' => $this->due_date,
                'paid_at' => $this->paid_at,
                'overdue_at' => $this->overdue_at,
                'cancelled_at' => $this->cancelled_at,
            ],
            'values' => [
                'subtotal' => $this->subtotal,
                'discount_amount' => $this->discount_amount,
                'late_fee' => $this->late_fee,
                'interest' => $this->interest,
                'tax_amount' => $this->tax_amount,
                'total_amount' => $this->total_amount,
                'paid_amount' => $this->paid_amount,
                'remaining_amount' => $this->remaining_amount,
            ],
            'flags' => [
                'is_paid' => $this->is_paid,
                'is_overdue' => $this->is_overdue,
                'days_overdue' => $this->days_overdue,
                'late_fee_applied' => $this->late_fee_applied,
            ],
            'fiscal' => [
                'sat_number' => $this->sat_number,
                'sat_key' => $this->sat_key,
                'sat_qrcode' => $this->sat_qrcode,
            ],
            // Relacionamentos
            'filho' => new FilhoResource($this->whenLoaded('filho')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
        ];
    }
}