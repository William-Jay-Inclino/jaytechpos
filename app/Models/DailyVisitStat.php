<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyVisitStat extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'daily_visit_stats';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'total_visits',
        'unique_visits',
        'page_views',
        'top_page',
        'top_referer',
        'mobile_visits',
        'desktop_visits',
        'tablet_visits',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'datetime',
        'total_visits' => 'integer',
        'unique_visits' => 'integer',
        'page_views' => 'integer',
        'mobile_visits' => 'integer',
        'desktop_visits' => 'integer',
        'tablet_visits' => 'integer',
    ];
}
