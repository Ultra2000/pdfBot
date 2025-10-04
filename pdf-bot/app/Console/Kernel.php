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
        // Clean up expired documents daily at 2 AM
        $schedule->command('documents:cleanup-expired --force')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/cleanup.log'));

        // Optional: Run a smaller cleanup every hour during business hours
        $schedule->command('documents:cleanup-expired --force')
                 ->hourly()
                 ->between('9:00', '18:00')
                 ->withoutOverlapping()
                 ->runInBackground();
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
