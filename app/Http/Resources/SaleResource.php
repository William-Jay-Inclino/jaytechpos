<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'transaction_date' => $this->transaction_date->toISOString(),
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer?->name,
            'total_amount' => (float) $this->total_amount,
            'paid_amount' => (float) $this->paid_amount,
            'payment_type' => $this->payment_type,
            'is_utang' => $this->payment_type === 'utang',
            'notes' => $this->notes,
            'cashier' => $this->user->name,
            'items' => SalesItemResource::collection($this->whenLoaded('salesItems')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
