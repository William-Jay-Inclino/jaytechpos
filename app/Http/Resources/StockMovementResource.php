<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
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
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'quantity' => number_format((float) $this->quantity, 2, '.', ''),
            'reference' => $this->reference,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ] + $this->conditionalAttributes();
    }

    /**
     * Get conditional attributes based on loaded relationships.
     *
     * @return array<string, mixed>
     */
    protected function conditionalAttributes(): array
    {
        $attributes = [];

        if ($this->relationLoaded('product')) {
            $attributes['product'] = [
                'id' => $this->product->id,
                'product_name' => $this->product->product_name,
            ];
        }

        return $attributes;
    }
}
