<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Helper class for generating Indonesian labels for monitoring data
 */
class MonitoringLabelHelper
{
    /**
     * Get Indonesian weekday name from Carbon date
     */
    public static function weekdayNameId(Carbon $date): string
    {
        return match ($date->dayOfWeek) {
            Carbon::MONDAY => 'Senin',
            Carbon::TUESDAY => 'Selasa',
            Carbon::WEDNESDAY => 'Rabu',
            Carbon::THURSDAY => 'Kamis',
            Carbon::FRIDAY => 'Jumat',
            Carbon::SATURDAY => 'Sabtu',
            Carbon::SUNDAY => 'Minggu',
        };
    }

    /**
     * Get Indonesian month abbreviation from month number (1-12)
     */
    public static function monthAbbrevId(int $month): string
    {
        return match ($month) {
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agt',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
            default => throw new \InvalidArgumentException("Invalid month: {$month}"),
        };
    }
}
