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
            'date_formatted' => $this->date?->format('M j, Y') ?? '—',
            'total_visits' => $this->total_visits ?? 0,
            'unique_visits' => $this->unique_visits ?? 0,
            'page_views' => $this->page_views ?? 0,
            'top_page' => $this->top_page ?? '—',
            'top_referer' => $this->top_referer ?? '—',
            'mobile_visits' => $this->mobile_visits ?? 0,
            'desktop_visits' => $this->desktop_visits ?? 0,
            'tablet_visits' => $this->tablet_visits ?? 0,
        ];
    }
}
