<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisit extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'site_visits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'ip_address',
        'user_agent',
        'referer',
        'page_url',
        'country',
        'region',
        'city',
        'device_type',
        'browser',
        'os',
        'is_bot',
        'is_unique',
        'page_views',
        'visited_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_bot' => 'boolean',
        'is_unique' => 'boolean',
        'page_views' => 'integer',
        'visited_at' => 'datetime',
    ];
}
