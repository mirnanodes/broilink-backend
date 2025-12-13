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
        // Create Roles
        $adminRole = Role::create([
            'name' => 'Admin',
            'description' => 'System Administrator'
        ]);

        $ownerRole = Role::create([
            'name' => 'Owner',
            'description' => 'Farm Owner'
        ]);

        $peternakRole = Role::create([
            'name' => 'Peternak',
            'description' => 'Farm Worker'
        ]);

        // Create Admin
        $admin = User::create([
            'role_id' => $adminRole->role_id,
            'username' => 'admin',
            'email' => 'admin@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'System Administrator',
            'phone_number' => '081234567890',
            'status' => 'active',
            'date_joined' => now()->subMonths(7),
            'last_login' => now()
        ]);

        // Create 3 Owners
        $budi = User::create([
            'role_id' => $ownerRole->role_id,
            'username' => 'budi.santoso',
            'email' => 'budi.santoso@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Budi Santoso',
            'phone_number' => '123456789',
            'status' => 'active',
            'date_joined' => now()->subMonths(7),
            'last_login' => now()->subDays(1)
        ]);

        $siti = User::create([
            'role_id' => $ownerRole->role_id,
            'username' => 'siti.rahayu',
            'email' => 'siti.rahayu@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Siti Rahayu',
            'phone_number' => '234567890',
            'status' => 'active',
            'date_joined' => now()->subMonths(6),
            'last_login' => now()->subDays(2)
        ]);

        $agus = User::create([
            'role_id' => $ownerRole->role_id,
            'username' => 'agus.wijaya',
            'email' => 'agus.wijaya@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Agus Wijaya',
            'phone_number' => '345678901',
            'status' => 'active',
            'date_joined' => now()->subMonths(6),
            'last_login' => now()->subDays(3)
        ]);

        // Create 3 Peternaks (one for each farm)
        $ahmad = User::create([
            'role_id' => $peternakRole->role_id,
            'username' => 'ahmad.fauzi',
            'email' => 'ahmad.fauzi@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Ahmad Fauzi',
            'phone_number' => '081234567801',
            'status' => 'active',
            'date_joined' => now()->subMonths(7),
            'last_login' => now()
        ]);

        $sri = User::create([
            'role_id' => $peternakRole->role_id,
            'username' => 'sri.wahyuni',
            'email' => 'sri.wahyuni@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Sri Wahyuni',
            'phone_number' => '081234567802',
            'status' => 'active',
            'date_joined' => now()->subMonths(6),
            'last_login' => now()->subHours(2)
        ]);

        $bambang = User::create([
            'role_id' => $peternakRole->role_id,
            'username' => 'bambang.sutrisno',
            'email' => 'bambang.sutrisno@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Bambang Sutrisno',
            'phone_number' => '081234567803',
            'status' => 'active',
            'date_joined' => now()->subMonths(6),
            'last_login' => now()->subHours(5)
        ]);

        // Create 3 Farms
        $farm1 = Farm::create([
            'owner_id' => $budi->user_id,
            'peternak_id' => $ahmad->user_id,
            'farm_name' => 'Kandang Ayam Sleman - Tridadi',
            'location' => 'Sleman - Tridadi',
            'initial_population' => 5000,
            'initial_weight' => 0.045,
            'farm_area' => 1000,
            'created_at' => now()->subMonths(7),
            'updated_at' => now()
        ]);

        $farm2 = Farm::create([
            'owner_id' => $siti->user_id,
            'peternak_id' => $sri->user_id,
            'farm_name' => 'Kandang Ayam Bantul - Pandak',
            'location' => 'Bantul - Pandak',
            'initial_population' => 4500,
            'initial_weight' => 0.042,
            'farm_area' => 850,
            'created_at' => now()->subMonths(6),
            'updated_at' => now()
        ]);

        $farm3 = Farm::create([
            'owner_id' => $agus->user_id,
            'peternak_id' => $bambang->user_id,
            'farm_name' => 'Kandang Ayam Kulon Progo - Wates',
            'location' => 'Kulon Progo - Wates',
            'initial_population' => 5500,
            'initial_weight' => 0.048,
            'farm_area' => 1200,
            'created_at' => now()->subMonths(6),
            'updated_at' => now()
        ]);

        $farms = [$farm1, $farm2, $farm3];

        // Create Farm Configs with thresholds that will generate varied statuses
        foreach ($farms as $farm) {
            FarmConfig::create([
                'farm_id' => $farm->farm_id,
                'parameter_name' => 'suhu_normal_min',
                'value' => 28
            ]);

            FarmConfig::create([
                'farm_id' => $farm->farm_id,
                'parameter_name' => 'suhu_normal_max',
                'value' => 32
            ]);

            FarmConfig::create([
                'farm_id' => $farm->farm_id,
                'parameter_name' => 'suhu_kritis_rendah',
                'value' => 24
            ]);

            FarmConfig::create([
                'farm_id' => $farm->farm_id,
                'parameter_name' => 'suhu_kritis_tinggi',
                'value' => 36
            ]);

            FarmConfig::create([
                'farm_id' => $farm->farm_id,
                'parameter_name' => 'kelembapan_normal_min',
                'value' => 50
            ]);

            FarmConfig::create([
                'farm_id' => $farm->farm_id,
                'parameter_name' => 'kelembapan_normal_max',
                'value' => 70
            ]);

            FarmConfig::create([
                'farm_id' => $farm->farm_id,
                'parameter_name' => 'kelembapan_kritis_rendah',
                'value' => 40
            ]);

            FarmConfig::create([
                'farm_id' => $farm->farm_id,
                'parameter_name' => 'kelembapan_kritis_tinggi',
                'value' => 80
            ]);

            FarmConfig::create([
                'farm_id' => $farm->farm_id,
                'parameter_name' => 'amonia_max',
                'value' => 20
            ]);

            FarmConfig::create([
                'farm_id' => $farm->farm_id,
                'parameter_name' => 'amonia_kritis',
                'value' => 30
            ]);
        }

        // Seed IoT Data: 6 months + 15 days buffer, every 10 minutes
        $this->command->info('Generating IoT data (6 months + 15 days, every 10 minutes)...');

        $startDate = now()->subMonths(6)->subDays(15);
        $endDate = now();
        $totalMinutes = $startDate->diffInMinutes($endDate);
        $dataPointsPerFarm = (int) ($totalMinutes / 10); // Every 10 minutes

        $this->command->info("Total data points per farm: {$dataPointsPerFarm}");

        foreach ($farms as $farmIndex => $farm) {
            $this->command->info("Generating IoT data for farm {$farm->farm_id}...");
            $batchSize = 500;
            $batch = [];

            for ($i = 0; $i < $dataPointsPerFarm; $i++) {
                $timestamp = $startDate->copy()->addMinutes($i * 10);

                $hour = $timestamp->hour;
                $isDay = $hour >= 6 && $hour <= 18;

                // Base values with day/night variation
                $baseTemp = $isDay ? rand(29, 32) : rand(26, 29);
                $baseHumidity = $isDay ? rand(55, 65) : rand(65, 75);
                $baseAmmonia = rand(8, 15);

                // Add noise and occasional outliers to generate varied statuses
                $temp = $baseTemp + rand(-3, 3);
                $humidity = $baseHumidity + rand(-8, 8);
                $ammonia = $baseAmmonia + rand(-4, 4);

                // Occasionally generate outliers (10% chance) for Waspada/Bahaya
                if (rand(1, 100) <= 10) {
                    if (rand(0, 1)) {
                        // Temperature outlier
                        $temp = rand(0, 1) ? rand(24, 27) : rand(33, 35);
                    } else {
                        // Ammonia outlier
                        $ammonia = rand(18, 25);
                    }
                }

                // Very rare critical outliers (2% chance) for Bahaya
                if (rand(1, 100) <= 2) {
                    $temp = rand(0, 1) ? rand(20, 23) : rand(37, 39);
                    $ammonia = rand(28, 35);
                }

                $batch[] = [
                    'farm_id' => $farm->farm_id,
                    'temperature' => max(18, min(42, $temp)),
                    'humidity' => max(30, min(95, $humidity)),
                    'ammonia' => max(0, min(50, $ammonia)),
                    'data_source' => 'system_seed',
                    'timestamp' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];

                if (count($batch) >= $batchSize) {
                    IotData::insert($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                IotData::insert($batch);
            }
        }

        $this->command->info('IoT data created: ' . IotData::count() . ' records');

        // Seed Manual Data: 6 months + 15 days, daily reports
        $this->command->info('Generating Manual data (daily for 6 months + 15 days)...');

        $totalDays = (int) $startDate->diffInDays($endDate);

        foreach ($farms as $farm) {
            // Only generate manual data if farm has peternak
            if (!$farm->peternak_id) {
                continue;
            }

            for ($day = 0; $day < $totalDays; $day++) {
                $reportDate = $startDate->copy()->addDays($day);
                $dayInCycle = $day % 35; // 35-day broiler cycle

                // Progressive weight gain
                $initialWeight = 0.040; // 40g
                $targetWeight = 1.900;  // 1900g at day 35
                $growthPerDay = ($targetWeight - $initialWeight) / 35;
                $expectedWeight = $initialWeight + ($growthPerDay * $dayInCycle);
                $actualWeight = $expectedWeight + (rand(-20, 20) / 1000);

                // Feed consumption increases with age (6-8% of body weight for flock)
                $avgBirds = 5000 - ($dayInCycle * 3); // Slight mortality
                $feedPerBird = $actualWeight * 0.07;
                $totalFeed = $feedPerBird * $avgBirds;

                // Water consumption 1.8-2x feed
                $waterMultiplier = 1.8 + (rand(0, 20) / 100);

                ManualData::create([
                    'farm_id' => $farm->farm_id,
                    'user_id_input' => $farm->peternak_id,
                    'report_date' => $reportDate->toDateString(),
                    'konsumsi_pakan' => round(max(50, min(500, $totalFeed + rand(-10, 10))), 1),
                    'konsumsi_air' => round(max(100, min(1000, $totalFeed * $waterMultiplier)), 1),
                    'rata_rata_bobot' => round(max(0.030, min(2.200, $actualWeight)), 3),
                    'jumlah_kematian' => $dayInCycle <= 7 ? rand(3, 8) : rand(0, 5),
                    'created_at' => $reportDate->copy()->addHours(18),
                    'updated_at' => $reportDate->copy()->addHours(18)
                ]);
            }
        }

        $this->command->info('Manual data created: ' . ManualData::count() . ' records');

        // Seed Request Logs (minimum 7 rows with realistic data)
        $this->command->info('Generating Request Logs...');

        $requestTypes = ['Tambah Kandang', 'Tambah Peternak'];
        $statuses = ['pending', 'approved', 'rejected'];

        $requests = [
            [
                'user_id' => $budi->user_id,
                'sender_name' => 'Budi Santoso',
                'phone_number' => '081298765432',
                'request_type' => 'Tambah Kandang',
                'request_content' => json_encode([
                    'farm_name' => 'Kandang Broiler Mlati',
                    'location' => 'Sleman - Mlati',
                    'population' => 6000,
                    'area' => 1200,
                    'keterangan' => 'Mohon bantuan untuk penambahan kandang baru dengan kapasitas 6000 ekor'
                ]),
                'status' => 'approved',
                'sent_time' => now()->subDays(45)
            ],
            [
                'user_id' => $siti->user_id,
                'sender_name' => 'Siti Rahayu',
                'phone_number' => '081387654321',
                'request_type' => 'Tambah Peternak',
                'request_content' => json_encode([
                    'nama_peternak' => 'Joko Widodo',
                    'email' => 'joko.widodo@example.com',
                    'phone' => '081234567899',
                    'keterangan' => 'Request penambahan peternak untuk Kandang Bantul'
                ]),
                'status' => 'pending',
                'sent_time' => now()->subDays(12)
            ],
            [
                'user_id' => $agus->user_id,
                'sender_name' => 'Agus Wijaya',
                'phone_number' => '081276543210',
                'request_type' => 'Tambah Kandang',
                'request_content' => json_encode([
                    'farm_name' => 'Kandang Layer Pengasih',
                    'location' => 'Kulon Progo - Pengasih',
                    'population' => 4000,
                    'area' => 800,
                    'keterangan' => 'Penambahan kandang layer untuk diversifikasi usaha'
                ]),
                'status' => 'rejected',
                'sent_time' => now()->subDays(60)
            ],
            [
                'user_id' => $budi->user_id,
                'sender_name' => 'Budi Santoso',
                'phone_number' => '081298765432',
                'request_type' => 'Tambah Peternak',
                'request_content' => json_encode([
                    'nama_peternak' => 'Sri Wahyuni',
                    'email' => 'sri.wahyuni@example.com',
                    'phone' => '081345678901',
                    'keterangan' => 'Butuh peternak tambahan untuk shift malam'
                ]),
                'status' => 'approved',
                'sent_time' => now()->subDays(30)
            ],
            [
                'user_id' => 0, // Guest request
                'sender_name' => 'Dewi Kusuma',
                'phone_number' => '081456789012',
                'request_type' => 'Menunggu Detail Login',
                'request_content' => 'dewi.kusuma@gmail.com',
                'status' => 'pending',
                'sent_time' => now()->subDays(5)
            ],
            [
                'user_id' => $siti->user_id,
                'sender_name' => 'Siti Rahayu',
                'phone_number' => '081387654321',
                'request_type' => 'Tambah Kandang',
                'request_content' => json_encode([
                    'farm_name' => 'Kandang Broiler Sewon',
                    'location' => 'Bantul - Sewon',
                    'population' => 5500,
                    'area' => 1100,
                    'keterangan' => 'Ekspansi bisnis peternakan broiler di area Sewon'
                ]),
                'status' => 'approved',
                'sent_time' => now()->subDays(20)
            ],
            [
                'user_id' => 0, // Guest request
                'sender_name' => 'Bambang Sutrisno',
                'phone_number' => '081567890123',
                'request_type' => 'Masalah Data',
                'request_content' => 'bambang.sutrisno@yahoo.com',
                'status' => 'rejected',
                'sent_time' => now()->subDays(8)
            ],
            [
                'user_id' => $agus->user_id,
                'sender_name' => 'Agus Wijaya',
                'phone_number' => '081276543210',
                'request_type' => 'Tambah Peternak',
                'request_content' => json_encode([
                    'nama_peternak' => 'Tuti Wulandari',
                    'email' => 'tuti.wulandari@example.com',
                    'phone' => '081234567888',
                    'keterangan' => 'Membutuhkan peternak berpengalaman untuk kandang baru'
                ]),
                'status' => 'pending',
                'sent_time' => now()->subDays(3)
            ],
        ];

        foreach ($requests as $request) {
            RequestLog::create([
                'user_id' => $request['user_id'],
                'sender_name' => $request['sender_name'],
                'phone_number' => $request['phone_number'],
                'request_type' => $request['request_type'],
                'request_content' => $request['request_content'],
                'status' => $request['status'],
                'sent_time' => $request['sent_time'],
                'created_at' => $request['sent_time'],
                'updated_at' => $request['sent_time']
            ]);
        }

        $this->command->info('Request logs created: ' . RequestLog::count() . ' records');

        $this->command->info('Database seeding completed successfully!');
        $this->command->info('Summary:');
        $this->command->info('- Users: ' . User::count());
        $this->command->info('- Farms: ' . Farm::count());
        $this->command->info('- IoT Data: ' . IotData::count());
        $this->command->info('- Manual Data: ' . ManualData::count());
        $this->command->info('- Request Logs: ' . RequestLog::count());
    }
}
