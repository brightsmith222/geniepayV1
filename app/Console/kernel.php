<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\File;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Register scheduled jobs here
        $schedule->command('transactions:requery')->everyFiveMinutes();

        // Daily log cleanup
        $schedule->call(function () {
            $logPath = storage_path('logs/laravel.log');
            if (File::exists($logPath)) {
                File::put($logPath, ''); // Wipe file content
            }
        })->dailyAt('01:00')->name('log:cleanup')->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
