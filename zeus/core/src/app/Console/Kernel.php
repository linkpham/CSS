<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Refresh dashboard cache every 15 minutes
        // This pre-caches all dashboard data for faster page loads
        $schedule->command('dashboard:refresh-cache')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/dashboard-cache.log'));
        
        // Force refresh cache daily at 23:30 (Vietnam time)
        // This ensures fresh data is available for the next day
        $schedule->command('dashboard:refresh-cache --force')
            ->dailyAt('23:30')
            ->timezone('Asia/Ho_Chi_Minh')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/dashboard-cache-daily.log'));

        // CareSoft sync - sync every 30 minutes for incremental updates
        $schedule->command('caresoft:sync --days=1')
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/caresoft-sync.log'));

        // CareSoft full weekly sync - Sunday at 2:00 AM
        $schedule->command('caresoft:sync --days=30')
            ->weeklyOn(0, '02:00')
            ->timezone('Asia/Ho_Chi_Minh')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/caresoft-sync-weekly.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
