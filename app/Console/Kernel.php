<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Refresh the local symbols master table on trading days only (needs cron /
        // a scheduler runner; on local dev just run `php artisan symbols:sync`).
        // Skips weekends and the exchange holidays managed in Settings → Refresh
        // calendar (config('settings.market_holidays') via marketHolidays()).
        $schedule->command('symbols:sync')
            ->weekdays()
            ->dailyAt('01:30')
            ->skip(function () {
                return in_array(
                    \Carbon\Carbon::now('Asia/Ho_Chi_Minh')->toDateString(),
                    marketHolidays(),
                    true
                );
            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
