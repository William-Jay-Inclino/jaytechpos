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
            'ip_address' => $this->ip_address ?? '—',
            'user_agent' => $this->user_agent,
            'referer' => $this->referer ?? '—',
            'page_url' => $this->page_url,
            'page_display' => $this->formatPageUrl($this->page_url),
            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,
            'location_formatted' => $this->formatLocation(),
            'device_type' => $this->device_type,
            'browser' => $this->browser,
            'os' => $this->os,
            'device_formatted' => $this->formatDevice(),
            'is_bot' => $this->is_bot,
            'is_unique' => $this->is_unique,
            'flags_formatted' => $this->formatFlags(),
            'page_views' => $this->page_views ?? 0,
            'visited_at' => $this->visited_at?->toIso8601String(),
            'visited_at_formatted' => $this->visited_at?->format('M j, g:i A') ?? '—',
        ];
    }

    private function formatLocation(): string
    {
        $parts = array_filter([$this->city, $this->region, $this->country]);

        return ! empty($parts) ? implode(', ', $parts) : '—';
    }

    private function formatDevice(): string
    {
        $parts = array_filter([$this->device_type, $this->browser, $this->os]);

        return ! empty($parts) ? implode(' · ', $parts) : '—';
    }

    private function formatFlags(): string
    {
        if ($this->is_bot) {
            return 'Bot';
        }

        if ($this->is_unique) {
            return 'Unique';
        }

        return '—';
    }

    private function formatPageUrl(?string $url): string
    {
        if (! $url) {
            return '—';
        }

        // If it's a relative path
        if (preg_match('/^\//', $url)) {
            return ($url === '/' || $url === '') ? 'Welcome Page' : rtrim($url, '/');
        }

        // Parse full URL
        $parsedUrl = parse_url($url);
        if ($parsedUrl && isset($parsedUrl['path'])) {
            $path = rtrim($parsedUrl['path'], '/');

            return ($path === '' || $path === '/') ? 'Welcome Page' : $path;
        }

        return $url;
    }
}
