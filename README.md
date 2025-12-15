# BroiLink Backend

Backend API untuk sistem monitoring kandang ayam broiler berbasis IoT. Dibangun pakai Laravel 12.

---

## Apa Aja Isinya?

Ini backend REST API yang handle:
- Login/logout user (pakai Sanctum token)
- CRUD user dan farm buat admin
- Dashboard monitoring buat owner dan peternak
- Terima data sensor IoT (suhu, kelembaban, amonia)
- Input data manual (pakan, air, kematian, bobot)
- Notifikasi alert via Telegram bot
- Export laporan

---

## Tech Stack

- PHP 8.2+
- Laravel 12
- MySQL
- Laravel Sanctum (auth token)
- Telegram Bot API (notifikasi)

---

## Dependencies

**Production (composer.json)**
- `laravel/framework` - Framework utama
- `laravel/sanctum` - API token authentication
- `laravel/tinker` - REPL untuk debugging

**Development**
- `fakerphp/faker` - Generate fake data untuk seeding
- `laravel/pint` - Code formatter
- `laravel/sail` - Docker environment
- `phpunit/phpunit` - Testing

---

## Struktur Folder

```
be-broilink-m/
├── app/
│   ├── Console/Commands/     # Artisan commands (FarmAlert.php buat bot)
│   ├── Http/Controllers/
│   │   ├── API/              # Semua controller API
│   │   │   ├── AuthController.php
│   │   │   ├── AdminController.php
│   │   │   ├── OwnerController.php
│   │   │   ├── PeternakController.php
│   │   │   ├── MonitoringAggregateController.php
│   │   │   └── ManualAnalysisAggregateController.php
│   │   └── SimulationController.php
│   ├── Models/               # Eloquent models
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Farm.php
│   │   ├── FarmConfig.php
│   │   ├── IotData.php
│   │   └── ManualData.php
│   └── Services/             # Business logic
│       ├── FarmStatusService.php
│       ├── MonitoringAggregateService.php
│       └── ManualAnalysisAggregateService.php
├── database/
│   ├── migrations/           # Struktur tabel
│   └── seeders/              # Data dummy
├── routes/
│   └── api.php               # Semua route API
└── config/                   # Konfigurasi Laravel
```

---

## Database & Relasi

**Tabel utama:**

```
roles
├── role_id (PK)
├── name (admin/owner/peternak)
└── description

users
├── user_id (PK)
├── role_id (FK → roles)
├── username, email, password
├── name, phone_number, profile_pic
├── status, date_joined, last_login

farms
├── farm_id (PK)
├── owner_id (FK → users)      # siapa yang punya
├── peternak_id (FK → users)   # siapa yang jaga (nullable)
├── farm_name, location
├── initial_population, initial_weight, farm_area

farm_config
├── config_id (PK)
├── farm_id (FK → farms)
├── parameter_name (suhu_min, suhu_max, dll)
├── value

iot_data
├── id (PK)
├── farm_id (FK → farms)
├── timestamp
├── temperature, humidity, ammonia
├── data_source (sensor/simulation)

manual_data
├── id (PK)
├── farm_id (FK → farms)
├── user_id_input (FK → users)  # siapa yang input
├── report_date
├── konsumsi_pakan, konsumsi_air
├── rata_rata_bobot, jumlah_kematian
```

**Relasi singkat:**
- 1 Role → banyak User
- 1 User (owner) → banyak Farm
- 1 Farm → 1 User (peternak)
- 1 Farm → banyak IotData
- 1 Farm → banyak ManualData
- 1 Farm → banyak FarmConfig

---

## Quick Start

```bash
# 1. Clone repo
git clone https://github.com/mirnanodes/broilink-backend.git
cd broilink-backend

# 2. Install dependencies
composer install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Setting database di .env
# DB_DATABASE=broilink
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Migrate & seed
php artisan migrate --seed

# 6. Jalankan
php artisan serve
```

Server jalan di `http://localhost:8000`

---

## Test Account

Semua password: `password`

- Admin: `admin@broilink.com`
- Owner: `owner@broilink.com`
- Peternak: `peternak@broilink.com`

---

## Role & Fitur

**Admin**
- Lihat semua user & farm
- CRUD user (owner & peternak)
- CRUD farm
- Assign peternak ke farm
- Setting config farm (threshold sensor)
- Upload data IoT via CSV
- Handle request dari owner

**Owner**
- Dashboard monitoring semua farm miliknya
- Lihat data sensor & manual per farm
- Export laporan
- Submit request ke admin

**Peternak**
- Dashboard monitoring farm yang di-assign
- Input data harian (pakan, air, kematian, bobot)
- Lihat riwayat input
- Update profile

---

## API Endpoints

### Auth
```
POST /api/login              # Login, dapat token
POST /api/logout             # Logout (perlu token)
POST /api/forgot-password    # Reset password
```

### Admin
```
GET    /api/admin/dashboard
GET    /api/admin/users
POST   /api/admin/users
GET    /api/admin/users/{id}
PUT    /api/admin/users/{id}
DELETE /api/admin/users/{id}

GET    /api/admin/farms
POST   /api/admin/farms
GET    /api/admin/farms/{id}
PUT    /api/admin/farms/{id}/assign-peternak
PUT    /api/admin/farms/{id}/update-area
PUT    /api/admin/farms/{id}/update-population
GET    /api/admin/farms/{id}/config
PUT    /api/admin/farms/{id}/config
POST   /api/admin/farms/{id}/config/reset
POST   /api/admin/farms/{id}/iot/upload

GET    /api/admin/owners
GET    /api/admin/owners/{owner_id}/farms
GET    /api/admin/peternaks/{owner_id}

GET    /api/admin/requests
PUT    /api/admin/requests/{id}/status
```

### Owner
```
GET  /api/owner/dashboard
GET  /api/owner/export/{farm_id}
POST /api/owner/requests
```

### Peternak
```
GET  /api/peternak/dashboard
POST /api/peternak/reports
GET  /api/peternak/profile
PUT  /api/peternak/profile
POST /api/peternak/profile/photo
POST /api/peternak/otp/send
POST /api/peternak/otp/verify
```

### Monitoring & Analysis (Agregasi)
```
GET /api/monitoring/aggregate    # Data sensor agregat
GET /api/analysis/aggregate      # Data manual agregat
```

### Simulation
```
POST /api/simulation/iot         # Input data IoT manual (tanpa hardware)
```

---

## Telegram Bot

Bot untuk kirim notifikasi alert kalau sensor abnormal.

**Setup:**
1. Buat bot di @BotFather, dapat token
2. Tambah di `.env`:
   ```
   TELEGRAM_BOT_TOKEN=your_bot_token
   ```
3. Jalankan bot:
   ```bash
   php artisan farm:run-bot
   ```

Bot bakal polling terus dan kirim alert kalau ada data sensor yang lewat threshold.

---

## Simulation Endpoint

Buat testing tanpa hardware IoT. Kirim data sensor manual:

```
POST /api/simulation/iot
Content-Type: application/json

{
  "farm_id": 1,
  "temperature": 32.5,
  "humidity": 65,
  "ammonia": 15
}
```

Response:
```json
{
  "success": true,
  "message": "Data IoT berhasil disimpan",
  "data": {
    "farm_id": 1,
    "temperature": 32.5,
    "humidity": 65,
    "ammonia": 15,
    "timestamp": "2025-01-15 10:30:00",
    "data_source": "simulation"
  }
}
```

---

## Environment Variables

Yang penting di `.env`:

```env
APP_NAME=BroiLink
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=broilink
DB_USERNAME=root
DB_PASSWORD=

TELEGRAM_BOT_TOKEN=your_bot_token_here
```

---

## Artisan Commands

```bash
php artisan serve              # Jalankan server
php artisan migrate            # Jalankan migration
php artisan migrate:fresh --seed  # Reset DB + seed
php artisan farm:run-bot       # Jalankan Telegram bot
php artisan tinker             # REPL untuk debug
```

---

## Notes

- Semua endpoint kecuali login & simulation butuh token Bearer
- Token didapat dari response login
- Format: `Authorization: Bearer {token}`
- Data IoT disini baru simulation endpoint


## PAD1 - Kelompok 1
Smartfarm - Broilink
