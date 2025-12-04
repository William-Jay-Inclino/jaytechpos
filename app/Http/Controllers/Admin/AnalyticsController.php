<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteAnalyticsRequest;
use App\Http\Resources\DailyVisitStatResource;
use App\Http\Resources\SiteVisitResource;
use App\Models\DailyVisitStat;
use App\Models\SiteVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $dailyVisitStats = DailyVisitStatResource::collection(
            DailyVisitStat::query()
                ->orderBy('date', 'desc')
                ->paginate(10, ['*'], 'daily_page')
                ->withQueryString()
        );

        $siteVisits = SiteVisitResource::collection(
            SiteVisit::query()
                ->latest()
                ->paginate(20, ['*'], 'site_page')
                ->withQueryString()
        );

        return Inertia::render('admin/Analytics', [
            'daily_visit_stats' => $dailyVisitStats,
            'site_visits' => $siteVisits,
        ]);
    }

    public function destroyDailyStat(DailyVisitStat $dailyVisitStat): JsonResponse
    {
        return $this->destroyModel($dailyVisitStat, 'Daily visit stat deleted successfully.');
    }

    public function destroySiteVisit(SiteVisit $siteVisit): JsonResponse
    {
        return $this->destroyModel($siteVisit, 'Site visit deleted successfully.');
    }

    private function destroyModel(DailyVisitStat|SiteVisit $model, string $message): JsonResponse
    {
        Gate::authorize('delete', $model);

        $model->delete();

        return response()->json([
            'success' => true,
            'msg' => $message,
        ]);
    }

    public function bulkDeleteDailyStats(BulkDeleteAnalyticsRequest $request): RedirectResponse
    {
        return $this->bulkDelete($request, DailyVisitStat::class, 'daily visit stat(s)');
    }

    public function bulkDeleteSiteVisits(BulkDeleteAnalyticsRequest $request): RedirectResponse
    {
        return $this->bulkDelete($request, SiteVisit::class, 'site visit(s)');
    }

    private function bulkDelete(BulkDeleteAnalyticsRequest $request, string $modelClass, string $itemName): RedirectResponse
    {
        $validated = $request->validated();

        $count = $modelClass::query()
            ->whereBetween('created_at', [
                $validated['start_date'].' 00:00:00',
                $validated['end_date'].' 23:59:59',
            ])
            ->delete();

        return redirect()->back()->with('success', "Successfully deleted {$count} {$itemName}.");
    }
}
