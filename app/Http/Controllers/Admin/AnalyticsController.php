<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyVisitStat;
use App\Models\SiteVisit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        // Use distinct page query names so the two paginators don't stomp each other
        $dailyVisitStats = DailyVisitStat::query()
            ->orderBy('date', 'desc')
            ->paginate(5, ['*'], 'daily_page')
            ->withQueryString()
            ->through(function (DailyVisitStat $stat) {
                return [
                    'id' => $stat->id,
                    'date' => $stat->date?->toDateString(),
                    'total_visits' => $stat->total_visits,
                    'unique_visits' => $stat->unique_visits,
                    'page_views' => $stat->page_views,
                    'top_page' => $stat->top_page,
                    'top_referer' => $stat->top_referer,
                    'mobile_visits' => $stat->mobile_visits,
                    'desktop_visits' => $stat->desktop_visits,
                    'tablet_visits' => $stat->tablet_visits,
                ];
            });

        $siteVisits = SiteVisit::query()
            ->latest()
            ->paginate(15, ['*'], 'site_page')
            ->withQueryString()
            ->through(function (SiteVisit $visit) {
                return [
                    'id' => $visit->id,
                    'session_id' => $visit->session_id,
                    'ip_address' => $visit->ip_address,
                    'user_agent' => $visit->user_agent,
                    'referer' => $visit->referer,
                    'page_url' => $visit->page_url,
                    'country' => $visit->country,
                    'region' => $visit->region,
                    'city' => $visit->city,
                    'device_type' => $visit->device_type,
                    'browser' => $visit->browser,
                    'os' => $visit->os,
                    'is_bot' => $visit->is_bot,
                    'is_unique' => $visit->is_unique,
                    'page_views' => $visit->page_views,
                    'visited_at' => $visit->visited_at?->toIso8601String(),
                ];
            });

        return Inertia::render('admin/Analytics', [
            'daily_visit_stats' => $dailyVisitStats,
            'site_visits' => $siteVisits,
        ]);
    }
}
