<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set timezone for PHP
        date_default_timezone_set('Asia/Jakarta');
        
        // Set timezone for Carbon
        Carbon::setLocale('id');
        
        // Set timezone for MySQL connection (wrapped in try-catch for CLI commands)
        try {
            if (config('database.default') === 'mysql') {
                DB::statement("SET time_zone = '+07:00'");
            }
        } catch (\Exception $e) {
            // Silently fail during migrations or when DB is not available
        }
    }
}
