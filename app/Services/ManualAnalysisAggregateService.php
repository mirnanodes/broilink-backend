<?php

namespace App\Services;

use App\Support\MonitoringLabelHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Service for aggregating Manual Analysis data
 * SINGLE SOURCE for manual analysis charts + overview
 */
class ManualAnalysisAggregateService
{
    public function __construct(
        private readonly FarmStatusService $statusService
    ) {}

    /**
     * Aggregate IoT data for the given farm, date, and range
     */
    public function aggregate(int $farmId, string $date, string $range): array
    {
        $carbonDate = Carbon::parse($date);

        return match ($range) {
            '1_day'    => $this->aggregateOneDay($farmId, $carbonDate),
            '1_week'   => $this->aggregateOneWeek($farmId, $carbonDate),
            '1_month'  => $this->aggregateOneMonth($farmId, $carbonDate),
            '6_months' => $this->aggregateSixMonths($farmId, $carbonDate),
            default    => throw new LogicException("Invalid range: {$range}"),
        };
    }

    /**
     * -------- 1 DAY (6 buckets, 4h each) --------
     * Groups manual data by updated_at hour into 4-hour buckets
     * Uses updated_at so that edited reports show in the correct time bucket
     */
    private function aggregateOneDay(int $farmId, Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end   = $date->copy()->endOfDay();

        // Query manual_data grouped by 4-hour buckets based on updated_at
        // updated_at reflects when data was last modified (more accurate for "recent activity")
        $results = DB::table('manual_data')
            ->selectRaw('
                FLOOR(HOUR(updated_at) / 4) AS bucket,
                SUM(konsumsi_pakan) AS total_feed,
                SUM(konsumsi_air) AS total_water,
                AVG(rata_rata_bobot) AS avg_weight,
                SUM(jumlah_kematian) AS total_mortality
            ')
            ->where('farm_id', $farmId)
            ->whereBetween('updated_at', [$start, $end])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->keyBy('bucket');

        // 6 buckets: 00:00, 04:00, 08:00, 12:00, 16:00, 20:00
        $labels    = ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'];
        $feed      = [];
        $water     = [];
        $avgWeight = [];
        $mortality = [];

        for ($i = 0; $i < 6; $i++) {
            $feed[]      = isset($results[$i]) ? (int)round($results[$i]->total_feed) : 0;
            $water[]     = isset($results[$i]) ? (int)round($results[$i]->total_water) : 0;
            $avgWeight[] = isset($results[$i]) ? (int)round($results[$i]->avg_weight) : 0;
            $mortality[] = isset($results[$i]) ? (int)$results[$i]->total_mortality : 0;
        }

        return $this->withOverview($farmId, [
            'labels'       => $labels,
            'feed'         => $feed,
            'water'        => $water,
            'avg_weight'   => $avgWeight,
            'mortality'    => $mortality,
            'meta' => [
                'range'   => '1_day',
                'farm_id' => $farmId,
                'start'   => $start->toIso8601String(),
                'end'     => $end->toIso8601String(),
            ],
        ]);
    }

    /**
     * -------- 1 WEEK (7 days) --------
     * ✅ FIXED: Query manual_data instead of iot_data
     */
    private function aggregateOneWeek(int $farmId, Carbon $date): array
    {
        $end   = $date->copy()->endOfDay();
        $start = $date->copy()->subDays(6)->startOfDay();

        // ✅ FIX: Query manual_data table
        $results = DB::table('manual_data')
            ->selectRaw('
                report_date AS date,
                SUM(konsumsi_pakan) AS total_feed,
                SUM(konsumsi_air) AS total_water,
                AVG(rata_rata_bobot) AS avg_weight,
                SUM(jumlah_kematian) AS total_mortality
            ')
            ->where('farm_id', $farmId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->get()
            ->keyBy('date');

        $labels    = [];
        $feed      = [];
        $water     = [];
        $avgWeight = [];
        $mortality = [];

        $current = $start->copy();
        for ($i = 0; $i < 7; $i++) {
            $key = $current->toDateString();

            $labels[]    = MonitoringLabelHelper::weekdayNameId($current);
            $feed[]      = isset($results[$key]) ? (int)round($results[$key]->total_feed) : null;
            $water[]     = isset($results[$key]) ? (int)round($results[$key]->total_water) : null;
            $avgWeight[] = isset($results[$key]) ? (int)round($results[$key]->avg_weight) : null;
            $mortality[] = isset($results[$key]) ? (int)$results[$key]->total_mortality : null;

            $current->addDay();
        }

        return $this->withOverview($farmId, [
            'labels'       => $labels,
            'feed'         => $feed,
            'water'        => $water,
            'avg_weight'   => $avgWeight,
            'mortality'    => $mortality,
            'meta' => [
                'range'   => '1_week',
                'farm_id' => $farmId,
                'start'   => $start->toIso8601String(),
                'end'     => $end->toIso8601String(),
            ],
        ]);
    }

    /**
     * -------- 1 MONTH (4 weeks) --------
     * ✅ FIXED: Query manual_data instead of iot_data
     */
    private function aggregateOneMonth(int $farmId, Carbon $date): array
    {
        $start = $date->copy()->startOfMonth();
        $end   = $date->copy()->endOfMonth();

        // ✅ FIX: Query manual_data table
        $results = DB::table('manual_data')
            ->selectRaw('
                FLOOR((DAY(report_date) - 1) / 7) + 1 AS week_of_month,
                SUM(konsumsi_pakan) AS total_feed,
                SUM(konsumsi_air) AS total_water,
                AVG(rata_rata_bobot) AS avg_weight,
                SUM(jumlah_kematian) AS total_mortality
            ')
            ->where('farm_id', $farmId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('week_of_month')
            ->orderBy('week_of_month')
            ->get()
            ->keyBy('week_of_month');

        $labels    = ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'];
        $feed      = [];
        $water     = [];
        $avgWeight = [];
        $mortality = [];

        for ($i = 1; $i <= 4; $i++) {
            $feed[]      = isset($results[$i]) ? (int)round($results[$i]->total_feed) : null;
            $water[]     = isset($results[$i]) ? (int)round($results[$i]->total_water) : null;
            $avgWeight[] = isset($results[$i]) ? (int)round($results[$i]->avg_weight) : null;
            $mortality[] = isset($results[$i]) ? (int)$results[$i]->total_mortality : null;
        }

        return $this->withOverview($farmId, [
            'labels'       => $labels,
            'feed'         => $feed,
            'water'        => $water,
            'avg_weight'   => $avgWeight,
            'mortality'    => $mortality,
            'meta' => [
                'range'   => '1_month',
                'farm_id' => $farmId,
                'start'   => $start->toIso8601String(),
                'end'     => $end->toIso8601String(),
            ],
        ]);
    }

    /**
     * -------- 6 MONTHS (calendar months) --------
     * ✅ FIXED: Query manual_data instead of iot_data
     */
    private function aggregateSixMonths(int $farmId, Carbon $date): array
    {
        $end   = $date->copy()->endOfMonth();
        $start = $date->copy()->subMonths(5)->startOfMonth();

        // ✅ FIX: Query manual_data table
        $results = DB::table('manual_data')
            ->selectRaw('
                YEAR(report_date) AS year,
                MONTH(report_date) AS month,
                SUM(konsumsi_pakan) AS total_feed,
                SUM(konsumsi_air) AS total_water,
                AVG(rata_rata_bobot) AS avg_weight,
                SUM(jumlah_kematian) AS total_mortality
            ')
            ->where('farm_id', $farmId)
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->keyBy(fn ($r) => "{$r->year}-{$r->month}");

        $labels    = [];
        $feed      = [];
        $water     = [];
        $avgWeight = [];
        $mortality = [];

        $current = $start->copy();
        for ($i = 0; $i < 6; $i++) {
            $key = "{$current->year}-{$current->month}";

            $labels[]    = MonitoringLabelHelper::monthAbbrevId($current->month);
            $feed[]      = isset($results[$key]) ? (int)round($results[$key]->total_feed) : null;
            $water[]     = isset($results[$key]) ? (int)round($results[$key]->total_water) : null;
            $avgWeight[] = isset($results[$key]) ? (int)round($results[$key]->avg_weight) : null;
            $mortality[] = isset($results[$key]) ? (int)$results[$key]->total_mortality : null;

            $current->addMonth();
        }

        return $this->withOverview($farmId, [
            'labels'       => $labels,
            'feed'         => $feed,
            'water'        => $water,
            'avg_weight'   => $avgWeight,
            'mortality'    => $mortality,
            'meta' => [
                'range'   => '6_months',
                'farm_id' => $farmId,
                'start'   => $start->toIso8601String(),
                'end'     => $end->toIso8601String(),
            ],
        ]);
    }

    /**
     * -------- OVERVIEW (status + latest sensor) --------
     */
    private function withOverview(int $farmId, array $payload): array
    {
        // ✅ FIX: Use IotData model instead of DB::table to avoid type mismatch
        $latest = \App\Models\IotData::where('farm_id', $farmId)
            ->latest('timestamp')
            ->first();

        $config = DB::table('farm_config')
            ->where('farm_id', $farmId)
            ->pluck('value', 'parameter_name')
            ->toArray();

        $payload['overview'] = [
            'status'      => $this->statusService->determine($latest, $config),
            'temperature' => $latest?->temperature,
            'humidity'    => $latest?->humidity,
            'ammonia'     => $latest?->ammonia,
        ];

        return $payload;
    }
}
