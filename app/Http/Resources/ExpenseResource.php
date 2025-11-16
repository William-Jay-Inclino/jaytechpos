<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
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
            'name' => $this->name,
            'amount' => (float) $this->amount,
            'expense_date' => $this->expense_date?->toDateString(),
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

        if ($this->relationLoaded('category') && $this->category) {
            $attributes['category'] = [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'color' => $this->category->color,
            ];
        }

        if ($this->relationLoaded('user') && $this->user) {
            $attributes['user'] = [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ];
        }

        return $attributes;
    }
}
