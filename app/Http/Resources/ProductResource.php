<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'unit_id' => $this->unit_id,
            'product_name' => $this->product_name,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'unit_price' => (float) $this->unit_price,
            'cost_price' => (float) $this->cost_price,
            'status' => $this->status,
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

        if ($this->relationLoaded('productCategory') && $this->productCategory) {
            $attributes['product_category'] = [
                'id' => $this->productCategory->id,
                'name' => $this->productCategory->name,
                'description' => $this->productCategory->description,
                'status' => $this->productCategory->status,
            ];
        }

        if ($this->relationLoaded('unit') && $this->unit) {
            $attributes['unit'] = [
                'id' => $this->unit->id,
                'unit_name' => $this->unit->unit_name,
                'abbreviation' => $this->unit->abbreviation,
            ];
        }

        return $attributes;
    }
}
