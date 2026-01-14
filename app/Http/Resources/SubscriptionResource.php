<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id, // Assumindo que HasUuid usa uuid como chave ou atributo
            'plan_name' => $this->plan_name,
            'plan_description' => $this->plan_description,
            'amount' => $this->amount,
            'billing_cycle' => $this->billing_cycle,
            'billing_day' => $this->billing_day,
            'status' => $this->status,
            'status_reason' => $this->status_reason,
            'dates' => [
                'started_at' => $this->started_at,
                'first_billing_date' => $this->first_billing_date,
                'next_billing_date' => $this->next_billing_date,
                'paused_at' => $this->paused_at,
                'cancelled_at' => $this->cancelled_at,
                'ends_at' => $this->ends_at,
            ],
            'statistics' => [
                'invoices_count' => $this->invoices_count,
                'paid_invoices_count' => $this->paid_invoices_count,
                'total_paid' => $this->total_paid,
            ],
            // Relacionamentos
            'filho' => new FilhoResource($this->whenLoaded('filho')),
            'approved_by' => new UserResource($this->whenLoaded('approvedBy')),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}