<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;

trait LogsActivityWithRequest
{
    public function tapActivity($activity, string $eventName): void
    {
        $request = request();

        if ($request) {
            $activity->properties = $activity->properties->merge([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }

    abstract public function getActivitylogOptions(): LogOptions;
}
