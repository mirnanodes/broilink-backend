<?php

namespace App\Services;

use App\Support\MonitoringLabelHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Service for aggregating IoT monitoring data (environment sensors)
 */
class MonitoringAggregateService
{
    /**
     * Aggregate IoT data for the given farm, date, and range
     */
    public function aggregate(int $farmId, string $date, string $range): array
    {
        $carbonDate = Carbon::parse($date);

        return match ($range) {
            '1_day' => $this->aggregateOneDay($farmId, $carbonDate),
            '1_week' => $this->aggregateOneWeek($farmId, $carbonDate),
            '1_month' => $this->aggregateOneMonth($farmId, $carbonDate),
            '6_months' => $this->aggregateSixMonths($farmId, $carbonDate),
            default => throw new LogicException("Invalid range: {$range}"),
        };
    }

    /**
     * Aggregate data for a single day (6 buckets of 4 hours each)
     */
    private function aggregateOneDay(int $farmId, Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $results = DB::table('iot_data')
            ->selectRaw('
                FLOOR(HOUR(`timestamp`) / 4) AS bucket,
                ROUND(AVG(temperature), 0) AS avg_temp,
                ROUND(AVG(humidity), 0) AS avg_humidity,
                ROUND(AVG(ammonia), 0) AS avg_ammonia
            ')
            ->where('farm_id', $farmId)
            ->whereBetween('timestamp', [$start, $end])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->keyBy('bucket');

        $labels = ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'];
        $temperature = [];
        $humidity = [];
        $ammonia = [];

        for ($i = 0; $i < 6; $i++) {
            if (isset($results[$i])) {
                $temperature[] = $results[$i]->avg_temp !== null ? (int) $results[$i]->avg_temp : null;
                $humidity[] = $results[$i]->avg_humidity !== null ? (int) $results[$i]->avg_humidity : null;
                $ammonia[] = $results[$i]->avg_ammonia !== null ? (int) $results[$i]->avg_ammonia : null;
            } else {
                $temperature[] = null;
                $humidity[] = null;
                $ammonia[] = null;
            }
        }

        return [
            'labels' => $labels,
            'temperature' => $temperature,
            'humidity' => $humidity,
            'ammonia' => $ammonia,
            'meta' => [
                'range' => '1_day',
                'farm_id' => $farmId,
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'source' => 'iot_data',
            ],
        ];
    }

    /**
     * Aggregate data for one week (7 days)
     */
    private function aggregateOneWeek(int $farmId, Carbon $date): array
    {
        $end = $date->copy()->endOfDay();
        $start = $date->copy()->subDays(6)->startOfDay();

        $results = DB::table('iot_data')
            ->selectRaw('
                DATE(`timestamp`) AS date,
                ROUND(AVG(temperature), 0) AS avg_temp,
                ROUND(AVG(humidity), 0) AS avg_humidity,
                ROUND(AVG(ammonia), 0) AS avg_ammonia
            ')
            ->where('farm_id', $farmId)
            ->whereBetween('timestamp', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $temperature = [];
        $humidity = [];
        $ammonia = [];

        $current = $start->copy();
        for ($i = 0; $i < 7; $i++) {
            $dateKey = $current->toDateString();
            $labels[] = MonitoringLabelHelper::weekdayNameId($current);

            if (isset($results[$dateKey])) {
                $temperature[] = $results[$dateKey]->avg_temp !== null ? (int) $results[$dateKey]->avg_temp : null;
                $humidity[] = $results[$dateKey]->avg_humidity !== null ? (int) $results[$dateKey]->avg_humidity : null;
                $ammonia[] = $results[$dateKey]->avg_ammonia !== null ? (int) $results[$dateKey]->avg_ammonia : null;
            } else {
                $temperature[] = null;
                $humidity[] = null;
                $ammonia[] = null;
            }

            $current->addDay();
        }

        return [
            'labels' => $labels,
            'temperature' => $temperature,
            'humidity' => $humidity,
            'ammonia' => $ammonia,
            'meta' => [
                'range' => '1_week',
                'farm_id' => $farmId,
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'source' => 'iot_data',
            ],
        ];
    }

    /**
     * Aggregate data for one month (4 week segments)
     */
    private function aggregateOneMonth(int $farmId, Carbon $date): array
    {
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        $results = DB::table('iot_data')
            ->selectRaw('
                FLOOR((DAY(`timestamp`) - 1) / 7) + 1 AS week_of_month,
                ROUND(AVG(temperature), 0) AS avg_temp,
                ROUND(AVG(humidity), 0) AS avg_humidity,
                ROUND(AVG(ammonia), 0) AS avg_ammonia
            ')
            ->where('farm_id', $farmId)
            ->whereBetween('timestamp', [$start, $end])
            ->groupBy('week_of_month')
            ->orderBy('week_of_month')
            ->get()
            ->keyBy('week_of_month');

        $labels = ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'];
        $temperature = [];
        $humidity = [];
        $ammonia = [];

        for ($i = 1; $i <= 4; $i++) {
            if (isset($results[$i])) {
                $temperature[] = $results[$i]->avg_temp !== null ? (int) $results[$i]->avg_temp : null;
                $humidity[] = $results[$i]->avg_humidity !== null ? (int) $results[$i]->avg_humidity : null;
                $ammonia[] = $results[$i]->avg_ammonia !== null ? (int) $results[$i]->avg_ammonia : null;
            } else {
                $temperature[] = null;
                $humidity[] = null;
                $ammonia[] = null;
            }
        }

        return [
            'labels' => $labels,
            'temperature' => $temperature,
            'humidity' => $humidity,
            'ammonia' => $ammonia,
            'meta' => [
                'range' => '1_month',
                'farm_id' => $farmId,
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'source' => 'iot_data',
            ],
        ];
    }

    /**
     * Aggregate data for six months (6 calendar months)
     */
    private function aggregateSixMonths(int $farmId, Carbon $date): array
    {
        $end = $date->copy()->endOfMonth();
        $start = $date->copy()->subMonths(5)->startOfMonth();

        $results = DB::table('iot_data')
            ->selectRaw('
                YEAR(`timestamp`) AS year,
                MONTH(`timestamp`) AS month,
                ROUND(AVG(temperature), 0) AS avg_temp,
                ROUND(AVG(humidity), 0) AS avg_humidity,
                ROUND(AVG(ammonia), 0) AS avg_ammonia
            ')
            ->where('farm_id', $farmId)
            ->whereBetween('timestamp', [$start, $end])
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->keyBy(fn($row) => "{$row->year}-{$row->month}");

        $labels = [];
        $temperature = [];
        $humidity = [];
        $ammonia = [];

        $current = $start->copy();
        for ($i = 0; $i < 6; $i++) {
            $key = "{$current->year}-{$current->month}";
            $labels[] = MonitoringLabelHelper::monthAbbrevId($current->month);

            if (isset($results[$key])) {
                $temperature[] = $results[$key]->avg_temp !== null ? (int) $results[$key]->avg_temp : null;
                $humidity[] = $results[$key]->avg_humidity !== null ? (int) $results[$key]->avg_humidity : null;
                $ammonia[] = $results[$key]->avg_ammonia !== null ? (int) $results[$key]->avg_ammonia : null;
            } else {
                $temperature[] = null;
                $humidity[] = null;
                $ammonia[] = null;
            }

            $current->addMonth();
        }

        return [
            'labels' => $labels,
            'temperature' => $temperature,
            'humidity' => $humidity,
            'ammonia' => $ammonia,
            'meta' => [
                'range' => '6_months',
                'farm_id' => $farmId,
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'source' => 'iot_data',
            ],
        ];
    }
}
