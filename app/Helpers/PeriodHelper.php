<?php

namespace App\Helpers;

use Carbon\Carbon;

class PeriodHelper
{
    /**
     * Return [start, end] Carbon instances for a named period.
     * Periods: today, yesterday, this_week, last_week, this_month, last_month, this_year, or custom (uses $from/$to).
     */
    public static function getRange(
        string $period = 'this_month',
        ?string $from = null,
        ?string $to = null
    ): array {
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];

            case 'yesterday':
                return [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()];

            case 'this_week':
                return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];

            case 'last_week':
                return [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()];

            case 'last_month':
                return [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()];

            case 'this_year':
                return [$now->copy()->startOfYear(), $now->copy()->endOfYear()];

            case 'custom':
                $start = $from ? Carbon::parse($from)->startOfDay() : $now->copy()->startOfMonth();
                $end   = $to   ? Carbon::parse($to)->endOfDay()     : $now->copy()->endOfDay();
                return [$start, $end];

            case 'this_month':
            default:
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        }
    }
}
