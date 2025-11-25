<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\StoreSiteVisitRequest;
use App\Models\SiteVisit;
use App\Models\DailyVisitStat;
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

        $data['ip_address'] = $request->ip();
        $location = Location::get($data['ip_address']);

        if($location) {
            $data['country'] = $location->countryName ?? null;
            $data['region'] = $location->regionName ?? null;
            $data['city'] = $location->cityName ?? null;
        }

        // Fill visited_at if missing
        if (empty($data['visited_at'])) {
            $data['visited_at'] = now();
        }

        // Device type detection (if missing)
        if (empty($data['device_type'])) {
            $ua = $data['user_agent'] ?? $request->userAgent();
            if (preg_match('/mobile|android|touch|webos|hpwos/i', $ua)) {
                $data['device_type'] = 'mobile';
            } elseif (preg_match('/tablet|ipad/i', $ua)) {
                $data['device_type'] = 'tablet';
            } else {
                $data['device_type'] = 'desktop';
            }
        }


        $data = $request->validated();
        $data['ip_address'] = $request->ip();

        // Get location info
        $location = Location::get($data['ip_address']);
        if ($location) {
            $data['country'] = $location->countryName ?? null;
            $data['region'] = $location->regionName ?? null;
            $data['city'] = $location->cityName ?? null;
        }

        // User agent string (for device, browser, os, bot)
        $ua = $data['user_agent'] ?? $request->userAgent();

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

        // Page views
        $data['page_views'] = $data['page_views'] ?? 1;

        // Bot detection
        $data['is_bot'] = $data['is_bot'] ?? (preg_match('/bot|crawl|spider|slurp|bingpreview/i', $ua) ? true : false);

        // Unique visit
        $data['is_unique'] = $data['is_unique'] ?? false;

        DB::transaction(function () use (&$data) {
            $siteVisit = SiteVisit::create($data);

            // Update or create daily stats
            $date = now()->startOfDay();
            $stat = DailyVisitStat::firstOrNew(['date' => $date]);

            $stat->total_visits = ($stat->total_visits ?? 0) + 1;
            $stat->page_views = ($stat->page_views ?? 0) + ($data['page_views'] ?? 1);

            // Unique visits: count only if is_unique is true
            if (!empty($data['is_unique'])) {
                $stat->unique_visits = ($stat->unique_visits ?? 0) + 1;
            }

            // Device type stats
            if (!empty($data['device_type'])) {
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
