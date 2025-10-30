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
            'amount_tendered' => $this->when(isset($this->amount_tendered), (float) $this->amount_tendered),
            'change_amount' => $this->when(isset($this->change_amount), (float) $this->change_amount),
            'balance_payment' => $this->when(isset($this->balance_payment), (float) $this->balance_payment),
            'original_customer_balance' => $this->when(isset($this->original_customer_balance), (float) $this->original_customer_balance),
            'new_customer_balance' => $this->when(isset($this->new_customer_balance), (float) $this->new_customer_balance),
            'payment_type' => $this->payment_type,
            'is_utang' => $this->payment_type === 'utang',
            'notes' => $this->notes,
            'cashier' => $this->user->name,
            'items' => SalesItemResource::collection($this->whenLoaded('salesItems'))->resolve(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
