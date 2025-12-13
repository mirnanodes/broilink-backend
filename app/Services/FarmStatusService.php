<?php

namespace App\Services;

use App\Models\IotData;

class FarmStatusService
{
    /**
     * Determine farm status based on latest IoT data and farm config
     */
    public function determine(?IotData $iot, array $config): string
    {
        if (!$iot || empty($config)) {
            return 'unknown';
        }

        // Critical thresholds (BAHAYA)
        if (
            $iot->temperature < ($config['suhu_kritis_rendah'] ?? -INF) ||
            $iot->temperature > ($config['suhu_kritis_tinggi'] ?? INF) ||
            $iot->humidity < ($config['kelembapan_kritis_rendah'] ?? -INF) ||
            $iot->humidity > ($config['kelembapan_kritis_tinggi'] ?? INF) ||
            $iot->ammonia > ($config['amonia_kritis'] ?? INF)
        ) {
            return 'bahaya';
        }

        // Warning thresholds (WASPADA)
        if (
            $iot->temperature < ($config['suhu_normal_min'] ?? -INF) ||
            $iot->temperature > ($config['suhu_normal_max'] ?? INF) ||
            $iot->humidity < ($config['kelembapan_normal_min'] ?? -INF) ||
            $iot->humidity > ($config['kelembapan_normal_max'] ?? INF) ||
            $iot->ammonia > ($config['amonia_max'] ?? INF)
        ) {
            return 'waspada';
        }

        return 'normal';
    }
}
