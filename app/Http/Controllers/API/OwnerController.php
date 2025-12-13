<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\IotData;
use App\Models\ManualData;
use App\Models\RequestLog;
use App\Services\FarmStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OwnerController extends Controller
{
    public function __construct(
        private readonly FarmStatusService $statusService
    ) {}

    /**
     * Owner Dashboard
     */
    public function dashboard(Request $request)
    {
        $owner = $request->user();

        $farms = Farm::where('owner_id', $owner->user_id)->get();

        $farmOverviews = $farms->map(function (Farm $farm) {
            $latest = IotData::where('farm_id', $farm->farm_id)
                ->latest('timestamp')
                ->first();

            $config = $this->getFarmConfig($farm->farm_id);

            return [
                'farm_id'     => $farm->farm_id,
                'farm_name'   => $farm->farm_name,
                'status'      => $this->statusService->determine($latest, $config),
                'temperature' => $latest?->temperature,
                'humidity'    => $latest?->humidity,
                'ammonia'     => $latest?->ammonia,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'farms'      => $farmOverviews,
                'activities' => $this->getActivities($owner->user_id),
            ]
        ]);
    }

    /**
     * Export farm data (CSV)
     */
    public function export(Request $request, int $farm_id)
    {
        $owner = $request->user();

        $farm = Farm::where('farm_id', $farm_id)
            ->where('owner_id', $owner->user_id)
            ->first();

        if (!$farm) {
            return response()->json([
                'success' => false,
                'message' => 'Farm not found or access denied'
            ], 404);
        }

        $type   = $request->get('type', 'all'); // iot | manual | all
        $period = $request->get('period', '30days');

        $days = match ($period) {
            '7days'   => 7,
            '30days'  => 30,
            '90days'  => 90,
            '180days' => 180,
            default   => 30,
        };

        $filename = 'export_' .
            str_replace(' ', '_', strtolower($farm->farm_name)) .
            '_' . now()->format('Ymd_His') . '.csv';

        $csv = '';

        if ($type === 'iot' || $type === 'all') {
            $iot = IotData::where('farm_id', $farm_id)
                ->where('timestamp', '>=', now()->subDays($days))
                ->orderBy('timestamp')
                ->get();

            if ($iot->isNotEmpty()) {
                $csv .= "DATA SENSOR IOT\n";
                $csv .= "Farm: {$farm->farm_name}\n";
                $csv .= "Exported at: " . now()->format('d/m/Y H:i:s') . "\n\n";
                $csv .= "Waktu,Suhu (°C),Kelembapan (%),Amonia (ppm),Sumber\n";

                foreach ($iot as $row) {
                    $csv .= sprintf(
                        "%s,%s,%s,%s,%s\n",
                        Carbon::parse($row->timestamp)->format('d/m/Y H:i:s'),
                        $row->temperature,
                        $row->humidity,
                        $row->ammonia,
                        $row->data_source
                    );
                }

                $csv .= "\n";
            }
        }

        if ($type === 'manual' || $type === 'all') {
            $manual = ManualData::where('farm_id', $farm_id)
                ->where('report_date', '>=', now()->subDays($days))
                ->orderBy('report_date')
                ->get();

            if ($manual->isNotEmpty()) {
                $csv .= "LAPORAN MANUAL HARIAN\n";
                $csv .= "Farm: {$farm->farm_name}\n";
                $csv .= "Exported at: " . now()->format('d/m/Y H:i:s') . "\n\n";
                $csv .= "Tanggal,Pakan (kg),Air (liter),Bobot (kg),Kematian\n";

                foreach ($manual as $row) {
                    $csv .= sprintf(
                        "%s,%s,%s,%s,%s\n",
                        Carbon::parse($row->report_date)->format('d/m/Y'),
                        $row->konsumsi_pakan,
                        $row->konsumsi_air,
                        $row->rata_rata_bobot,
                        $row->jumlah_kematian
                    );
                }
            }
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Submit request to admin
     */
    public function submitRequest(Request $request)
    {
        $owner = $request->user();

        $validated = $request->validate([
            'request_type'    => 'required|string',
            'request_content' => 'required|string'
        ]);

        $log = RequestLog::create([
            'user_id'        => $owner->user_id,
            'sender_name'    => $owner->name,
            'phone_number'   => $owner->phone_number,
            'request_type'   => $validated['request_type'],
            'request_content'=> $validated['request_content'],
            'status'         => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Request berhasil dikirim',
            'data'    => $log
        ], 201);
    }

    /**
     * Recent activities (IoT + Manual)
     */
    private function getActivities(int $ownerId)
    {
        $farmIds = Farm::where('owner_id', $ownerId)->pluck('farm_id');

        $iot = IotData::whereIn('farm_id', $farmIds)
            ->latest('timestamp')
            ->limit(10)
            ->get()
            ->map(fn ($d) => [
                'type'    => 'sensor',
                'message' => "Sensor: Suhu {$d->temperature}°C, RH {$d->humidity}%",
                'time'    => Carbon::parse($d->timestamp),
            ]);

        $manual = ManualData::whereIn('farm_id', $farmIds)
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($d) => [
                'type'    => 'manual',
                'message' => "Manual: Pakan {$d->konsumsi_pakan}kg, Bobot {$d->rata_rata_bobot}kg",
                'time'    => Carbon::parse($d->created_at),
            ]);

        return $iot
            ->merge($manual)
            ->sortByDesc('time')
            ->take(15)
            ->map(fn ($a) => [
                'type'    => $a['type'],
                'message' => $a['message'],
                'time'    => $a['time']->diffForHumans(),
            ])
            ->values();
    }

    /**
     * Get farm configuration
     */
    private function getFarmConfig(int $farmId): array
    {
        return DB::table('farm_config')
            ->where('farm_id', $farmId)
            ->pluck('value', 'parameter_name')
            ->toArray();
    }
}
