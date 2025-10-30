<?php

namespace App\Traits;

use DateTime;
use DateTimeZone;

trait HandlesTimezone
{
    /**
     * Convert a datetime string from Manila timezone to UTC for database storage
     */
    protected function manilaToUtc(string $dateTime): DateTime
    {
        $manilaDateTime = new DateTime($dateTime, new DateTimeZone('Asia/Manila'));
        $utcDateTime = clone $manilaDateTime;
        $utcDateTime->setTimezone(new DateTimeZone('UTC'));

        return $utcDateTime;
    }

    /**
     * Convert a UTC datetime to Manila timezone for display
     */
    protected function utcToManila(\Carbon\Carbon $dateTime): string
    {
        // Clone the original datetime to avoid modifying it
        $manilaDateTime = $dateTime->copy()->setTimezone('Asia/Manila');

        return $manilaDateTime->format('Y-m-d H:i:s');
    }

    /**
     * Get current Manila time as a formatted string
     */
    protected function nowInManila(): string
    {
        return now()->setTimezone('Asia/Manila')->format('Y-m-d H:i:s');
    }
}
