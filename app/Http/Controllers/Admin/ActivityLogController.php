<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteActivityLogsRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Activity::query()
            ->with('causer')
            ->latest();

        // Filter by event type
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filter by subject type
        if ($request->filled('subject_type')) {
            $query->where('subject_type', 'like', '%'.$request->subject_type.'%');
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Search in description or causer name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('causer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $activities = $query->paginate(15)->withQueryString();

        return Inertia::render('admin/ActivityLogs', [
            'activities' => ActivityResource::collection($activities),
            'filters' => [
                'search' => $request->search,
                'event' => $request->event,
                'subject_type' => $request->subject_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
        ]);
    }

    public function show(Activity $log): Response
    {
        $log->load('causer', 'subject');

        return Inertia::render('admin/ActivityLogShow', [
            'activity' => new ActivityResource($log),
        ]);
    }

    public function destroy(Activity $log): RedirectResponse
    {
        Gate::authorize('delete', $log);

        $log->delete();

        return redirect()->back()->with('success', 'Activity log deleted successfully.');
    }

    public function bulkDelete(BulkDeleteActivityLogsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $count = Activity::query()
            ->whereBetween('created_at', [
                $validated['start_date'].' 00:00:00',
                $validated['end_date'].' 23:59:59',
            ])
            ->delete();

        return redirect()->back()->with('success', "Successfully deleted {$count} activity log(s).");
    }
}
