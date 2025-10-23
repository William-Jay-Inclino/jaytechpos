<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product_name' => $this->product->product_name,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total_amount' => (float) ($this->quantity * $this->unit_price),
        ];
    }
}
