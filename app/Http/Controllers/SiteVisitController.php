<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteVisitRequest;
use App\Models\DailyVisitStat;
use App\Models\SiteVisit;
use Illuminate\Support\Facades\DB;
use Stevebauman\Location\Facades\Location;

class SiteVisitController extends Controller
{
    /**
     * Store a newly created site visit in storage.
     */
    public function store(StoreSiteVisitRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        // Server-determined values
        $data['ip_address'] = $request->ip();
        $data['visited_at'] = $data['visited_at'] ?? now();

        // User agent
        $ua = $data['user_agent'] ?? $request->userAgent();

        // Location (best-effort)
        $location = Location::get($data['ip_address']);
        if ($location) {
            $data['country'] = $location->countryName ?? null;
            $data['region'] = $location->regionName ?? null;
            $data['city'] = $location->cityName ?? null;
        }

        // Device type
        $data['device_type'] = $data['device_type'] ?? (
            preg_match('/mobile|android|touch|webos|hpwos/i', $ua) ? 'mobile'
            : (preg_match('/tablet|ipad/i', $ua) ? 'tablet' : 'desktop')
        );

        // Browser
        $data['browser'] = $data['browser'] ?? (
            strpos($ua, 'Chrome') !== false ? 'Chrome'
            : (strpos($ua, 'Firefox') !== false ? 'Firefox'
            : ((strpos($ua, 'Safari') !== false && strpos($ua, 'Chrome') === false) ? 'Safari'
            : (strpos($ua, 'Edge') !== false ? 'Edge'
            : ((strpos($ua, 'Opera') !== false || strpos($ua, 'OPR') !== false) ? 'Opera' : 'Other'))))
        );

        // OS
        $data['os'] = $data['os'] ?? (
            preg_match('/Windows/i', $ua) ? 'Windows'
            : (preg_match('/Macintosh|Mac OS/i', $ua) ? 'macOS'
            : (preg_match('/Linux/i', $ua) ? 'Linux'
            : (preg_match('/Android/i', $ua) ? 'Android'
            : (preg_match('/iPhone|iPad|iPod/i', $ua) ? 'iOS' : 'Other'))))
        );

        // Page views and bot detection defaults
        $data['page_views'] = $data['page_views'] ?? 1;
        $data['is_bot'] = $data['is_bot'] ?? (preg_match('/bot|crawl|spider|slurp|bingpreview/i', $ua) ? true : false);

        // Determine uniqueness server-side: prefer session_id, fallback to ip+ua for today's date
        $isUnique = false;
        if (! empty($data['session_id'])) {
            $exists = SiteVisit::where('session_id', $data['session_id'])
                ->whereDate('visited_at', $data['visited_at'])->exists();
            $isUnique = ! $exists;
        } else {
            $exists = SiteVisit::where('ip_address', $data['ip_address'])
                ->where('user_agent', $ua)
                ->whereDate('visited_at', $data['visited_at'])->exists();
            $isUnique = ! $exists;
        }

        // Allow client to pass is_unique, but server calculation is authoritative when missing
        $data['is_unique'] = $data['is_unique'] ?? $isUnique;

        DB::transaction(function () use (&$data) {
            SiteVisit::create($data);

            // Update or create daily stats
            $date = now()->startOfDay();
            $stat = DailyVisitStat::firstOrNew(['date' => $date]);

            $stat->total_visits = ($stat->total_visits ?? 0) + 1;
            $stat->page_views = ($stat->page_views ?? 0) + ($data['page_views'] ?? 1);

            if (! empty($data['is_unique'])) {
                $stat->unique_visits = ($stat->unique_visits ?? 0) + 1;
            }

            if (! empty($data['device_type'])) {
                if ($data['device_type'] === 'mobile') {
                    $stat->mobile_visits = ($stat->mobile_visits ?? 0) + 1;
                } elseif ($data['device_type'] === 'desktop') {
                    $stat->desktop_visits = ($stat->desktop_visits ?? 0) + 1;
                } elseif ($data['device_type'] === 'tablet') {
                    $stat->tablet_visits = ($stat->tablet_visits ?? 0) + 1;
                }
            }

            $stat->save();
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 201);
    }
}
