<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteVisitResource extends JsonResource
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
            'session_id' => $this->session_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'referer' => $this->referer,
            'page_url' => $this->page_url,
            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,
            'device_type' => $this->device_type,
            'browser' => $this->browser,
            'os' => $this->os,
            'is_bot' => $this->is_bot,
            'is_unique' => $this->is_unique,
            'page_views' => $this->page_views,
            'visited_at' => $this->visited_at?->toIso8601String(),
        ];
    }
}
