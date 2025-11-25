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
        return Inertia::render('admin/Analytics', [
            // Use distinct page query names so the two paginators don't stomp each other
            'daily_visit_stats' => DailyVisitStat::query()
                ->orderBy('date', 'desc')
                ->paginate(5, ['*'], 'daily_page')
                ->withQueryString(),
            'site_visits' => SiteVisit::query()
                ->latest()
                ->paginate(15, ['*'], 'site_page')
                ->withQueryString(),
        ]);
    }
}
