# BroiLink Backend API

## Overview

BroiLink Backend is a Laravel 12-based REST API that powers an IoT-enabled farm management system for poultry operations. The system supports real-time environmental monitoring through IoT sensors and manual daily reporting by farm workers (Peternaks).

### Key Responsibilities
- User authentication and role-based authorization (Admin, Owner, Peternak)
- Farm and farm configuration management
- IoT sensor data ingestion and aggregation
- Manual daily report collection and analysis
- Real-time Telegram notifications for farm alerts
- Data export and analytics endpoints

### Technology Stack
- **Framework:** Laravel 12
- **PHP Version:** ^8.2
- **Database:** MySQL
- **Authentication:** Laravel Sanctum (Token-based)
- **Key Dependencies:**
  - Laravel Sanctum ^4.2 (API authentication)
  - Laravel Tinker ^2.10 (REPL)
  - Carbon (Date/time handling)

---

## Requirements

- PHP 8.2 or higher
- Composer 2.x
- MySQL 5.7+ or MariaDB 10.3+
- Node.js 18+ and npm (for frontend assets, if applicable)

---

## Installation & Setup

### 1. Clone and Install Dependencies

```bash
# Navigate to backend directory
cd be-broilink-m

# Install PHP dependencies
composer install
```

### 2. Environment Configuration

```bash
# Copy example environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Edit `.env` and configure your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=broilink_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

**Important:** Set your Telegram bot token if using notifications:
```env
TELEGRAM_BOT_TOKEN=your_telegram_bot_token_here
```

### 3. Database Setup

```bash
# Run migrations
php artisan migrate

# Seed database with default data (roles, users, farms)
php artisan db:seed

# Or run both in one command
php artisan migrate:fresh --seed
```

**Default Test Users** (all passwords: `password`):
- **Admin:** `admin` / `password`
- **Owner:** `budi.santoso` / `password`
- **Peternak:** `ahmad.fauzi` / `password`

See `database/seeders/DatabaseSeeder.php` for complete list of seeded users.

### 4. Start Development Server

```bash
# Option 1: Start Laravel development server only
php artisan serve
# Backend will run on http://localhost:8000

# Option 2: Start full stack (backend + queue + logs + frontend)
composer dev
```

---

## Project Structure

```
be-broilink-m/
├── app/
│   ├── DataTransferObjects/    # DTOs for type-safe data transfer
│   │   └── AggregateRequestDto.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── API/            # API Controllers organized by role
│   │   │       ├── AuthController.php
│   │   │       ├── AdminController.php
│   │   │       ├── OwnerController.php
│   │   │       ├── PeternakController.php
│   │   │       ├── MonitoringAggregateController.php
│   │   │       └── ManualAnalysisAggregateController.php
│   │   └── Requests/           # Form Request validation classes
│   │       └── AggregateRequest.php
│   ├── Models/                 # Eloquent models
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Farm.php
│   │   ├── FarmConfig.php      # EAV pattern for farm parameters
│   │   ├── IotData.php
│   │   ├── ManualData.php
│   │   └── RequestLog.php
│   ├── Providers/              # Service providers
│   │   └── AppServiceProvider.php
│   ├── Services/               # Business logic layer
│   │   ├── TelegramService.php
│   │   ├── FarmStatusService.php
│   │   ├── MonitoringAggregateService.php
│   │   └── ManualAnalysisAggregateService.php
│   └── Support/                # Helper classes
│       └── MonitoringLabelHelper.php
├── bootstrap/                  # Framework bootstrap files
├── config/                     # Configuration files
├── database/
│   ├── factories/              # Model factories
│   ├── migrations/             # Database migrations (16 tables)
│   └── seeders/                # Database seeders
│       └── DatabaseSeeder.php  # Main seeder with test data
├── public/                     # Web server document root
├── resources/                  # Views, raw assets
├── routes/
│   ├── api.php                 # API routes (primary)
│   ├── web.php                 # Web routes (minimal)
│   └── console.php             # Console commands
├── storage/                    # Logs, cache, uploads
├── tests/
│   ├── Feature/                # Feature tests
│   └── Unit/                   # Unit tests
├── .env.example                # Environment template
├── artisan                     # Artisan CLI entry point
├── composer.json               # PHP dependencies
└── phpunit.xml                 # PHPUnit configuration
```

---

## API Architecture

### Authentication

The API uses **Laravel Sanctum** for token-based authentication.

**Login Flow:**
1. Client sends credentials to `POST /api/login`
2. Server validates and returns bearer token
3. Client includes token in subsequent requests: `Authorization: Bearer {token}`

**Public Endpoints:**
- `POST /api/login` - User authentication
- `POST /api/forgot-password` - Password reset
- `POST /api/guest-report` - Guest request submission

**Protected Endpoints:**
All other endpoints require `auth:sanctum` middleware.

### Route Organization

Routes are organized by user role in `routes/api.php`:

```
/api/login                      [PUBLIC]
/api/logout                     [Protected]

/api/admin/*                    [Admin Role]
  ├── /dashboard
  ├── /users                    (CRUD)
  ├── /farms                    (CRUD)
  ├── /farms/{id}/config        (Farm configuration)
  ├── /farms/{id}/iot/upload    (CSV upload)
  ├── /requests                 (Request management)
  └── /broadcast                (Telegram notifications)

/api/owner/*                    [Owner Role]
  ├── /dashboard
  └── /export/{farm_id}         (CSV export)

/api/peternak/*                 [Peternak Role]
  ├── /dashboard
  ├── /reports                  (Submit daily reports)
  ├── /profile                  (Profile management)
  └── /otp/*                    (OTP verification)

/api/monitoring/aggregate       [All Authenticated]
/api/analysis/aggregate         [All Authenticated]
```

### Response Format

All API responses follow a consistent JSON structure:

```json
{
  "success": true|false,
  "message": "Optional message",
  "data": { ... }
}
```

Error responses include appropriate HTTP status codes (400, 401, 404, 422, 500).

---

## Database Schema

The system uses **16 tables** organized as follows:

### Core Tables
- `roles` - User roles (Admin, Owner, Peternak)
- `users` - System users with role relationships
- `farms` - Farm information (owner_id, peternak_id)
- `farm_config` - EAV pattern for farm configuration parameters

### Data Tables
- `iot_data` - Real-time sensor readings (temperature, humidity, ammonia)
- `manual_data` - Daily manual reports (feed, water, weight, mortality)
- `request_log` - User requests to admin
- `notification_log` - Telegram notification history

### Laravel System Tables
- `personal_access_tokens` - Sanctum authentication tokens
- `cache`, `cache_locks` - Application cache
- `jobs`, `job_batches`, `failed_jobs` - Queue system
- `sessions` - Session management

**Key Relationships:**
- User `belongsTo` Role
- User `hasMany` Farms (as owner)
- User `hasOne` Farm (as peternak via assignedFarm)
- Farm `belongsTo` User (owner)
- Farm `belongsTo` User (peternak)
- Farm `hasMany` FarmConfig (EAV pattern)
- Farm `hasMany` IotData
- Farm `hasMany` ManualData

---

## Service Layer

The application uses a service-oriented architecture for complex business logic:

### TelegramService
Handles Telegram bot notifications:
- `sendMessage($chatId, $message)` - Send message to user
- `broadcastMessage($chatIds, $message)` - Send to multiple users
- `formatFarmAlert($farmName, $status, $sensorData)` - Format alert message
- `formatAnnouncement($title, $message)` - Format broadcast message

### FarmStatusService
Determines farm environmental status:
- `determine($latestIotData, $config)` - Returns 'normal', 'waspada', or 'bahaya'

### MonitoringAggregateService
Aggregates IoT sensor data across time ranges:
- `aggregate($farmId, $date, $range)` - Main aggregation method
- Supported ranges: `1_day`, `1_week`, `1_month`, `6_months`
- Returns time-series data for temperature, humidity, ammonia

### ManualAnalysisAggregateService
Aggregates manual report data (feed, water, weight, mortality):
- Similar structure to MonitoringAggregateService
- Handles manual_data table aggregation

---

## Testing

### Running Tests

```bash
# Run all tests
composer test
# OR
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

### Current Test Coverage

**Note:** The project currently has minimal test coverage (only example tests). Comprehensive testing is recommended for production use.

---

## Development Commands

### Artisan Commands

```bash
# View all routes
php artisan route:list

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Database
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed

# Generate files
php artisan make:model ModelName
php artisan make:controller ControllerName
php artisan make:migration migration_name
php artisan make:seeder SeederName
php artisan make:request RequestName

# Queue management
php artisan queue:work
php artisan queue:listen
```

### Composer Scripts

```bash
# Full setup (install, migrate, seed)
composer setup

# Run development stack
composer dev

# Run tests
composer test
```

---

## API Testing

### Using cURL

```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"username":"admin","password":"password"}'

# Admin Dashboard (use token from login)
curl -X GET http://localhost:8000/api/admin/dashboard \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Using Postman

See `POSTMAN_TESTING_GUIDE.md` (if available in parent directory) for complete step-by-step instructions.

---

## Code Quality Standards

This project follows Laravel and PSR coding standards:

- **PSR-12** for code style
- **Laravel Naming Conventions**
  - Controllers: `PascalCase` + `Controller` suffix
  - Models: `PascalCase` (singular)
  - Migrations: `snake_case` with descriptive names
  - Routes: `kebab-case` for URIs
- **DocBlocks** on all public methods
- **Type declarations** for method parameters and return types

### Recommended Tools

```bash
# Laravel Pint (code formatting)
./vendor/bin/pint

# PHPStan (static analysis)
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse
```

---

## Security Considerations

- All passwords are hashed using Laravel's `Hash` facade (bcrypt)
- API routes protected with `auth:sanctum` middleware
- CSRF protection enabled for web routes
- SQL injection prevention via Eloquent ORM and query builder
- Input validation using Form Requests and controller validation

**Important:** Never commit `.env` file or expose sensitive credentials.

---

## Deployment

### Production Checklist

1. Set environment to production:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   ```

2. Optimize configuration:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   composer install --optimize-autoloader --no-dev
   ```

3. Set appropriate permissions:
   ```bash
   chmod -R 755 storage bootstrap/cache
   ```

4. Configure web server (Apache/Nginx) to point to `public/` directory

5. Set up queue worker as a system service:
   ```bash
   php artisan queue:work --daemon
   ```

6. Configure scheduled tasks (if any):
   ```bash
   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
   ```

---

## Troubleshooting

### Common Issues

**Database Connection Failed:**
- Verify `.env` database credentials
- Ensure MySQL service is running
- Test connection: `php artisan migrate:status`

**Permission Denied on storage/:**
```bash
chmod -R 775 storage bootstrap/cache
```

**Class Not Found:**
```bash
composer dump-autoload
```

**Migration Already Ran:**
```bash
php artisan migrate:fresh --seed  # WARNING: Drops all tables
```

---

## Contributing

See `CONTRIBUTING.md` for backend development guidelines.

---

## License

This project is proprietary software developed for BroiLink.

---

## Support

For technical issues or questions:
- Check Laravel documentation: https://laravel.com/docs/12.x
- Review API routes: `php artisan route:list`
- Check logs: `storage/logs/laravel.log`
