<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Farm;
use App\Models\FarmConfig;
use App\Models\IotData;
use App\Models\ManualData;
use App\Models\RequestLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. SETUP ROLES
        // ==========================================
        $this->command->info('Creating roles...');

        $adminRole = Role::create(['name' => 'Admin', 'description' => 'System Administrator']);
        $ownerRole = Role::create(['name' => 'Owner', 'description' => 'Farm Owner']);
        $peternakRole = Role::create(['name' => 'Peternak', 'description' => 'Farm Worker']);

        // ==========================================
        // 2. CREATE ADMIN
        // ==========================================
        $this->command->info('Creating admin...');

        User::create([
            'role_id' => $adminRole->role_id,
            'username' => 'admin',
            'email' => 'admin@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'System Administrator',
            'phone_number' => '081234567890',
            'status' => 'active',
            'date_joined' => now()->subMonths(12),
        ]);

        // ==========================================
        // 3. CREATE OWNERS (Hanya 2 Owner)
        // ==========================================
        $this->command->info('Creating 2 Owners...');

        // OWNER 1: Budi (Punya 1 Kandang)
        $budi = User::create([
            'role_id' => $ownerRole->role_id,
            'username' => 'budi.santoso',
            'email' => 'budi.santoso@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Budi Santoso',
            'phone_number' => '081298765001',
            'status' => 'active',
            'date_joined' => now()->subMonths(3),
        ]);

        // OWNER 2: Siti (Punya 2 Kandang)
        $siti = User::create([
            'role_id' => $ownerRole->role_id,
            'username' => 'siti.nurhaliza',
            'email' => 'siti.nurhaliza@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Siti Nurhaliza',
            'phone_number' => '081298765002',
            'status' => 'active',
            'date_joined' => now()->subMonths(3),
        ]);

        // ==========================================
        // 4. CREATE PETERNAK (Hanya 3 Peternak)
        // ==========================================
        $this->command->info('Creating 3 Peternaks...');

        // Peternak buat Budi (owner_id = budi)
        $ahmad = User::create([
            'role_id' => $peternakRole->role_id,
            'owner_id' => $budi->user_id, // Link langsung ke owner
            'username' => 'ahmad.fauzi',
            'email' => 'ahmad.fauzi@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Ahmad Fauzi',
            'phone_number' => '081234567801',
            'status' => 'active',
        ]);

        // Peternak 1 buat Siti (owner_id = siti)
        $eko = User::create([
            'role_id' => $peternakRole->role_id,
            'owner_id' => $siti->user_id, // Link langsung ke owner
            'username' => 'eko.prasetyo',
            'email' => 'eko.prasetyo@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Eko Prasetyo',
            'phone_number' => '081234567802',
            'status' => 'active',
        ]);

        // Peternak 2 buat Siti (owner_id = siti)
        $dian = User::create([
            'role_id' => $peternakRole->role_id,
            'owner_id' => $siti->user_id, // Link langsung ke owner
            'username' => 'dian.wulandari',
            'email' => 'dian.wulandari@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Dian Wulandari',
            'phone_number' => '081234567803',
            'status' => 'active',
        ]);

        // ==========================================
        // 5. CREATE FARMS / KANDANG (Total 3)
        // ==========================================
        $this->command->info('Creating 3 Farms...');

        // Kandang Budi (1)
        $farm1 = Farm::create([
            'owner_id' => $budi->user_id,
            'peternak_id' => $ahmad->user_id,
            'farm_name' => 'Kandang Sleman Utara',
            'location' => 'Sleman, Yogyakarta',
            'initial_population' => 5000,
            'initial_weight' => 0.045,
            'farm_area' => 1000,
            'created_at' => now()->subMonths(2),
        ]);

        // Kandang Siti (2)
        $farm2 = Farm::create([
            'owner_id' => $siti->user_id,
            'peternak_id' => $eko->user_id,
            'farm_name' => 'Kandang Bantul Timur',
            'location' => 'Bantul, Yogyakarta',
            'initial_population' => 3000,
            'initial_weight' => 0.042,
            'farm_area' => 850,
            'created_at' => now()->subMonths(2),
        ]);

        $farm3 = Farm::create([
            'owner_id' => $siti->user_id,
            'peternak_id' => $dian->user_id,
            'farm_name' => 'Kandang Godean Barat',
            'location' => 'Godean, Sleman',
            'initial_population' => 4000,
            'initial_weight' => 0.048,
            'farm_area' => 950,
            'created_at' => now()->subMonths(2),
        ]);

        $farms = [$farm1, $farm2, $farm3];

        // ==========================================
        // 6. CONFIGURATION
        // ==========================================
        foreach ($farms as $farm) {
            $configs = [
                'suhu_normal_min' => 28, 'suhu_normal_max' => 32,
                'kelembapan_normal_min' => 50, 'kelembapan_normal_max' => 70,
                'amonia_max' => 20
            ];
            foreach ($configs as $p => $v) {
                FarmConfig::create(['farm_id' => $farm->farm_id, 'parameter_name' => $p, 'value' => $v]);
            }
        }

        // ==========================================
        // 7. SEED IOT DATA (DIET VERSION: 2 BULAN, EXCLUDE TODAY)
        // ==========================================
        $this->command->info('Generating IoT data (Only last 2 months, excluding today)...');

        // Setting waktu mundur 2 bulan, end at YESTERDAY
        $startDate = now()->subMonths(2);
        $endDate = now()->subDay()->endOfDay(); // Exclude today
        $totalMinutes = $startDate->diffInMinutes($endDate);

        // Data per 10 menit
        $dataPointsPerFarm = (int) ($totalMinutes / 10);

        foreach ($farms as $farm) {
            $batch = [];
            // Loop data
            for ($i = 0; $i < $dataPointsPerFarm; $i++) {
                $timestamp = $startDate->copy()->addMinutes($i * 10);
                $isDay = $timestamp->hour >= 6 && $timestamp->hour <= 18;

                // Logika Suhu Realistis (Siang panas, malam adem)
                $baseTemp = $isDay ? rand(29, 33) : rand(26, 29);
                $temp = $baseTemp + (rand(-10, 10) / 10); // Variasi desimal

                // Logika Kelembapan (Malam lembab)
                $baseHum = $isDay ? rand(55, 65) : rand(70, 80);
                $hum = $baseHum + rand(-5, 5);

                $batch[] = [
                    'farm_id' => $farm->farm_id,
                    'temperature' => $temp,
                    'humidity' => $hum,
                    'ammonia' => rand(5, 15) + (rand(0, 50) / 10), // Random 5.0 - 20.0
                    'data_source' => 'system_seed',
                    'timestamp' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];

                // Insert per 500 data biar RAM hemat
                if (count($batch) >= 500) {
                    IotData::insert($batch);
                    $batch = [];
                }
            }
            // Insert sisa data
            if (!empty($batch)) IotData::insert($batch);
        }

        // ==========================================
        // 8. MANUAL DATA (DIET VERSION: 2 BULAN, EXCLUDE TODAY)
        // ==========================================
        $this->command->info('Generating Manual data (Daily for 2 months, excluding today)...');

        // End at YESTERDAY, not today (so fresh input from user is clean)
        $manualEndDate = now()->subDay()->endOfDay();
        $totalDays = (int) $startDate->diffInDays($manualEndDate);

        foreach ($farms as $farm) {
            for ($day = 0; $day < $totalDays; $day++) {
                $date = $startDate->copy()->addDays($day);

                // Simulasi ayam tumbuh (makin hari makin berat dalam gram)
                $dayInCycle = $day % 35; // Reset tiap 35 hari (panen)
                $baseWeight = 40; // 40 gram DOC
                $growthPerDay = 50; // +50 gram per hari
                $bobot = $baseWeight + ($dayInCycle * $growthPerDay); // dalam gram
                
                // Set timestamp for evening report (18:00)
                $reportTime = $date->copy()->setHour(18)->setMinute(0)->setSecond(0);

                ManualData::create([
                    'farm_id' => $farm->farm_id,
                    'user_id_input' => $farm->peternak_id,
                    'report_date' => $date->toDateString(),
                    'konsumsi_pakan' => rand(100, 300) + ($dayInCycle * 2),
                    'konsumsi_air' => rand(200, 500) + ($dayInCycle * 3),
                    'rata_rata_bobot' => $bobot, // dalam gram
                    'jumlah_kematian' => rand(0, 3),
                    'created_at' => $reportTime,
                    'updated_at' => $reportTime
                ]);
            }
        }

        // ==========================================
        // 9. REQUEST LOGS (Disesuaikan user yg ada)
        // ==========================================
        RequestLog::create([
            'user_id' => $budi->user_id,
            'sender_name' => 'Budi Santoso',
            'phone_number' => '081298765001',
            'request_type' => 'Tambah Kandang',
            'request_content' => json_encode(['farm_name' => 'Kandang Baru Budi', 'loc' => 'Sleman']),
            'status' => 'pending',
            'sent_time' => now()->subDays(2)
        ]);

        RequestLog::create([
            'user_id' => $siti->user_id,
            'sender_name' => 'Siti Nurhaliza',
            'phone_number' => '081298765002',
            'request_type' => 'Laporan Masalah',
            'request_content' => 'Kipas mati di kandang 2',
            'status' => 'approved',
            'sent_time' => now()->subDays(5)
        ]);

        $this->command->info('Seeding completed successfully.');
    }
}
