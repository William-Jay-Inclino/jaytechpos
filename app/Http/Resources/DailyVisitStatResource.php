<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyVisitStatResource extends JsonResource
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
            'date' => $this->date?->toDateString(),
            'total_visits' => $this->total_visits,
            'unique_visits' => $this->unique_visits,
            'page_views' => $this->page_views,
            'top_page' => $this->top_page,
            'top_referer' => $this->top_referer,
            'mobile_visits' => $this->mobile_visits,
            'desktop_visits' => $this->desktop_visits,
            'tablet_visits' => $this->tablet_visits,
        ];
    }
}
