<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/Analytics');
    }

    public function sales()
    {
        return Inertia::render('admin/Analytics');
    }

    public function products()
    {
        return Inertia::render('admin/Analytics');
    }

    public function customers()
    {
        return Inertia::render('admin/Analytics');
    }
}
