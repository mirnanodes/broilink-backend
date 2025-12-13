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
        // 1. CREATE ROLES
        // ==========================================
        $this->command->info('Creating roles...');

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

        // ==========================================
        // 2. CREATE ADMIN
        // ==========================================
        $this->command->info('Creating admin user...');

        $admin = User::create([
            'role_id' => $adminRole->role_id,
            'username' => 'admin',
            'email' => 'admin@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'System Administrator',
            'phone_number' => '081234567890',
            'status' => 'active',
            'date_joined' => now()->subMonths(12),
            'last_login' => now()
        ]);

        // ==========================================
        // 3. CREATE OWNERS (3 owners, different scenarios)
        // ==========================================
        $this->command->info('Creating owners...');

        // OWNER 1: Budi Santoso - 1 FARM (Simple scenario)
        $budi = User::create([
            'role_id' => $ownerRole->role_id,
            'username' => 'budi.santoso',
            'email' => 'budi.santoso@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Budi Santoso',
            'phone_number' => '081298765001',
            'status' => 'active',
            'date_joined' => now()->subMonths(10),
            'last_login' => now()->subDays(1)
        ]);

        // OWNER 2: Siti Nurhaliza - 2 FARMS (Multi-farm scenario)
        $siti = User::create([
            'role_id' => $ownerRole->role_id,
            'username' => 'siti.nurhaliza',
            'email' => 'siti.nurhaliza@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Siti Nurhaliza',
            'phone_number' => '081298765002',
            'status' => 'active',
            'date_joined' => now()->subMonths(9),
            'last_login' => now()->subHours(3)
        ]);

        // OWNER 3: Bambang Setiawan - 3 FARMS (Large operation scenario)
        $bambangOwner = User::create([
            'role_id' => $ownerRole->role_id,
            'username' => 'bambang.setiawan',
            'email' => 'bambang.setiawan@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Bambang Setiawan',
            'phone_number' => '081298765003',
            'status' => 'active',
            'date_joined' => now()->subMonths(11),
            'last_login' => now()->subDays(2)
        ]);

        // ==========================================
        // 4. CREATE PETERNAKS (7 peternaks)
        // ==========================================
        $this->command->info('Creating peternaks...');

        // Peternak 1: Ahmad Fauzi (for Budi's farm)
        $ahmad = User::create([
            'role_id' => $peternakRole->role_id,
            'username' => 'ahmad.fauzi',
            'email' => 'ahmad.fauzi@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Ahmad Fauzi',
            'phone_number' => '081234567801',
            'status' => 'active',
            'date_joined' => now()->subMonths(10),
            'last_login' => now()->subHours(1)
        ]);

        // Peternak 2: Eko Prasetyo (for Siti's farm 1)
        $eko = User::create([
            'role_id' => $peternakRole->role_id,
            'username' => 'eko.prasetyo',
            'email' => 'eko.prasetyo@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Eko Prasetyo',
            'phone_number' => '081234567802',
            'status' => 'active',
            'date_joined' => now()->subMonths(9),
            'last_login' => now()->subHours(2)
        ]);

        // Peternak 3: Dian Wulandari (for Siti's farm 2)
        $dian = User::create([
            'role_id' => $peternakRole->role_id,
            'username' => 'dian.wulandari',
            'email' => 'dian.wulandari@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Dian Wulandari',
            'phone_number' => '081234567803',
            'status' => 'active',
            'date_joined' => now()->subMonths(9),
            'last_login' => now()->subHours(4)
        ]);

        // Peternak 4: Wahyu Nugroho (for Bambang's farm 1)
        $wahyu = User::create([
            'role_id' => $peternakRole->role_id,
            'username' => 'wahyu.nugroho',
            'email' => 'wahyu.nugroho@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Wahyu Nugroho',
            'phone_number' => '081234567804',
            'status' => 'active',
            'date_joined' => now()->subMonths(11),
            'last_login' => now()->subHours(5)
        ]);

        // Peternak 5: Sri Mulyani (for Bambang's farm 2)
        $sri = User::create([
            'role_id' => $peternakRole->role_id,
            'username' => 'sri.mulyani',
            'email' => 'sri.mulyani@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Sri Mulyani',
            'phone_number' => '081234567805',
            'status' => 'active',
            'date_joined' => now()->subMonths(11),
            'last_login' => now()->subHours(6)
        ]);

        // Peternak 6: Fitri Handayani (for Bambang's farm 3)
        $fitri = User::create([
            'role_id' => $peternakRole->role_id,
            'username' => 'fitri.handayani',
            'email' => 'fitri.handayani@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Fitri Handayani',
            'phone_number' => '081234567806',
            'status' => 'active',
            'date_joined' => now()->subMonths(11),
            'last_login' => now()->subHours(8)
        ]);

        // Peternak 7: Rizky Ramadhan (UNASSIGNED - for testing)
        $rizky = User::create([
            'role_id' => $peternakRole->role_id,
            'username' => 'rizky.ramadhan',
            'email' => 'rizky.ramadhan@broilink.com',
            'password' => Hash::make('password'),
            'name' => 'Rizky Ramadhan',
            'phone_number' => '081234567807',
            'status' => 'active',
            'date_joined' => now()->subDays(15),
            'last_login' => now()->subDays(2)
        ]);

        // ==========================================
        // 5. CREATE FARMS (6 farms total)
        // ==========================================
        $this->command->info('Creating farms...');

        // BUDI's FARM (1 farm)
        $farm1 = Farm::create([
            'owner_id' => $budi->user_id,
            'peternak_id' => $ahmad->user_id,
            'farm_name' => 'Kandang Sleman Utara',
            'location' => 'Jl. Kaliurang Km 12, Sleman, Yogyakarta',
            'initial_population' => 5000,
            'initial_weight' => 0.045,
            'farm_area' => 1000,
            'created_at' => now()->subMonths(10),
            'updated_at' => now()
        ]);

        // SITI's FARMS (2 farms)
        $farm2 = Farm::create([
            'owner_id' => $siti->user_id,
            'peternak_id' => $eko->user_id,
            'farm_name' => 'Kandang Bantul Timur',
            'location' => 'Jl. Parangtritis Km 8, Bantul, Yogyakarta',
            'initial_population' => 3000,
            'initial_weight' => 0.042,
            'farm_area' => 850,
            'created_at' => now()->subMonths(9),
            'updated_at' => now()
        ]);

        $farm3 = Farm::create([
            'owner_id' => $siti->user_id,
            'peternak_id' => $dian->user_id,
            'farm_name' => 'Kandang Godean Barat',
            'location' => 'Jl. Godean Km 5, Sleman, Yogyakarta',
            'initial_population' => 4000,
            'initial_weight' => 0.048,
            'farm_area' => 950,
            'created_at' => now()->subMonths(8),
            'updated_at' => now()
        ]);

        // BAMBANG's FARMS (3 farms)
        $farm4 = Farm::create([
            'owner_id' => $bambangOwner->user_id,
            'peternak_id' => $wahyu->user_id,
            'farm_name' => 'Kandang Wonosari Selatan',
            'location' => 'Jl. Wonosari Km 7, Gunungkidul, Yogyakarta',
            'initial_population' => 5500,
            'initial_weight' => 0.043,
            'farm_area' => 1200,
            'created_at' => now()->subMonths(11),
            'updated_at' => now()
        ]);

        $farm5 = Farm::create([
            'owner_id' => $bambangOwner->user_id,
            'peternak_id' => $sri->user_id,
            'farm_name' => 'Kandang Wates Tengah',
            'location' => 'Jl. Wates Km 10, Kulon Progo, Yogyakarta',
            'initial_population' => 4500,
            'initial_weight' => 0.046,
            'farm_area' => 1100,
            'created_at' => now()->subMonths(11),
            'updated_at' => now()
        ]);

        $farm6 = Farm::create([
            'owner_id' => $bambangOwner->user_id,
            'peternak_id' => $fitri->user_id,
            'farm_name' => 'Kandang Solo Timur',
            'location' => 'Jl. Solo Km 15, Sleman, Yogyakarta',
            'initial_population' => 6000,
            'initial_weight' => 0.044,
            'farm_area' => 1300,
            'created_at' => now()->subMonths(10),
            'updated_at' => now()
        ]);

        $farms = [$farm1, $farm2, $farm3, $farm4, $farm5, $farm6];

        // ==========================================
        // 6. CREATE FARM CONFIGS
        // ==========================================
        $this->command->info('Creating farm configurations...');

        foreach ($farms as $farm) {
            $configs = [
                'suhu_normal_min' => 28,
                'suhu_normal_max' => 32,
                'suhu_kritis_rendah' => 25,
                'suhu_kritis_tinggi' => 35,
                'kelembapan_normal_min' => 50,
                'kelembapan_normal_max' => 70,
                'kelembapan_kritis_rendah' => 40,
                'kelembapan_kritis_tinggi' => 80,
                'amonia_max' => 20,
                'amonia_kritis' => 30
            ];

            foreach ($configs as $param => $value) {
                FarmConfig::create([
                    'farm_id' => $farm->farm_id,
                    'parameter_name' => $param,
                    'value' => $value
                ]);
            }
        }

        // ==========================================
        // 7. SEED IOT DATA (6 months, every 10 minutes)
        // ==========================================
        $this->command->info('Generating IoT data (6 months, every 10 minutes)...');

        $startDate = now()->subMonths(6)->subDays(15);
        $endDate = now();
        $totalMinutes = $startDate->diffInMinutes($endDate);
        $dataPointsPerFarm = (int) ($totalMinutes / 10);

        $this->command->info("Total data points per farm: {$dataPointsPerFarm}");

        foreach ($farms as $farmIndex => $farm) {
            $this->command->info("Generating IoT data for farm {$farm->farm_id} ({$farm->farm_name})...");
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

                // Add noise and occasional outliers
                $temp = $baseTemp + rand(-3, 3);
                $humidity = $baseHumidity + rand(-8, 8);
                $ammonia = $baseAmmonia + rand(-4, 4);

                // 10% chance for warning conditions
                if (rand(1, 100) <= 10) {
                    if (rand(0, 1)) {
                        $temp = rand(0, 1) ? rand(24, 27) : rand(33, 35);
                    } else {
                        $ammonia = rand(18, 25);
                    }
                }

                // 2% chance for critical conditions
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

        // ==========================================
        // 8. SEED MANUAL DATA (6 months, daily)
        // ==========================================
        $this->command->info('Generating Manual data (daily for 6 months)...');

        $totalDays = (int) $startDate->diffInDays($endDate);

        foreach ($farms as $farm) {
            // Only generate if peternak assigned
            if (!$farm->peternak_id) {
                continue;
            }

            for ($day = 0; $day < $totalDays; $day++) {
                $reportDate = $startDate->copy()->addDays($day);
                $dayInCycle = $day % 35; // 35-day broiler cycle

                // Progressive weight gain
                $initialWeight = 0.040;
                $targetWeight = 1.900;
                $growthPerDay = ($targetWeight - $initialWeight) / 35;
                $expectedWeight = $initialWeight + ($growthPerDay * $dayInCycle);
                $actualWeight = $expectedWeight + (rand(-20, 20) / 1000);

                // Feed consumption
                $avgBirds = $farm->initial_population - ($dayInCycle * 3);
                $feedPerBird = $actualWeight * 0.07;
                $totalFeed = $feedPerBird * $avgBirds;

                // Water consumption
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

        // ==========================================
        // 9. SEED REQUEST LOGS
        // ==========================================
        $this->command->info('Generating Request Logs...');

        $requests = [
            [
                'user_id' => $budi->user_id,
                'sender_name' => 'Budi Santoso',
                'phone_number' => '081298765001',
                'request_type' => 'Tambah Kandang',
                'request_content' => json_encode([
                    'farm_name' => 'Kandang Broiler Mlati',
                    'location' => 'Sleman - Mlati',
                    'population' => 6000,
                    'area' => 1200,
                    'keterangan' => 'Mohon bantuan untuk penambahan kandang baru'
                ]),
                'status' => 'approved',
                'sent_time' => now()->subDays(45)
            ],
            [
                'user_id' => $siti->user_id,
                'sender_name' => 'Siti Nurhaliza',
                'phone_number' => '081298765002',
                'request_type' => 'Tambah Peternak',
                'request_content' => json_encode([
                    'nama_peternak' => 'Joko Widodo',
                    'email' => 'joko.widodo@example.com',
                    'phone' => '081234567899'
                ]),
                'status' => 'pending',
                'sent_time' => now()->subDays(12)
            ],
            [
                'user_id' => $bambangOwner->user_id,
                'sender_name' => 'Bambang Setiawan',
                'phone_number' => '081298765003',
                'request_type' => 'Tambah Kandang',
                'request_content' => json_encode([
                    'farm_name' => 'Kandang Layer Pengasih',
                    'location' => 'Kulon Progo - Pengasih',
                    'population' => 4000
                ]),
                'status' => 'rejected',
                'sent_time' => now()->subDays(60)
            ],
            [
                'user_id' => 0,
                'sender_name' => 'Dewi Kusuma',
                'phone_number' => '081456789012',
                'request_type' => 'Menunggu Detail Login',
                'request_content' => 'dewi.kusuma@gmail.com',
                'status' => 'pending',
                'sent_time' => now()->subDays(5)
            ],
            [
                'user_id' => $siti->user_id,
                'sender_name' => 'Siti Nurhaliza',
                'phone_number' => '081298765002',
                'request_type' => 'Tambah Kandang',
                'request_content' => json_encode([
                    'farm_name' => 'Kandang Broiler Sewon',
                    'location' => 'Bantul - Sewon',
                    'population' => 5500
                ]),
                'status' => 'approved',
                'sent_time' => now()->subDays(20)
            ],
        ];

        foreach ($requests as $request) {
            RequestLog::create($request);
        }

        $this->command->info('Request logs created: ' . RequestLog::count() . ' records');

        // ==========================================
        // 10. SUMMARY
        // ==========================================
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('DATABASE SEEDING COMPLETED!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('  - Users: ' . User::count());
        $this->command->info('  - Farms: ' . Farm::count());
        $this->command->info('  - IoT Data: ' . IotData::count());
        $this->command->info('  - Manual Data: ' . ManualData::count());
        $this->command->info('  - Request Logs: ' . RequestLog::count());
        $this->command->info('');
        $this->command->info('🔐 Test Credentials (all passwords: "password"):');
        $this->command->info('  Admin: admin');
        $this->command->info('');
        $this->command->info('  📍 Scenario 1 - Owner with 1 farm:');
        $this->command->info('    Owner: budi.santoso → Kandang Sleman Utara → Peternak: Ahmad Fauzi');
        $this->command->info('');
        $this->command->info('  📍 Scenario 2 - Owner with 2 farms:');
        $this->command->info('    Owner: siti.nurhaliza');
        $this->command->info('      ├─ Kandang Bantul Timur → Peternak: Eko Prasetyo');
        $this->command->info('      └─ Kandang Godean Barat → Peternak: Dian Wulandari');
        $this->command->info('');
        $this->command->info('  📍 Scenario 3 - Owner with 3 farms:');
        $this->command->info('    Owner: bambang.setiawan');
        $this->command->info('      ├─ Kandang Wonosari Selatan → Peternak: Wahyu Nugroho');
        $this->command->info('      ├─ Kandang Wates Tengah → Peternak: Sri Mulyani');
        $this->command->info('      └─ Kandang Solo Timur → Peternak: Fitri Handayani');
        $this->command->info('');
        $this->command->info('  ⚠️  Unassigned Peternak: rizky.ramadhan (for testing)');
        $this->command->info('');
        $this->command->info('========================================');
    }
}
