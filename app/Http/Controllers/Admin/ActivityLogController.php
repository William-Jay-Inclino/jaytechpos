<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/ActivityLogs', [
            'logs' => [
                'data' => [],
                'links' => [],
                'meta' => [],
            ],
        ]);
    }

    public function show($log)
    {
        return Inertia::render('admin/ActivityLogs');
    }
}
