<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\PurgeOldServers;
use App\Console\VerifiedUserCount;
use App\Console\ActiveServerCount;
use App\Console\PunishmentExpiry;
use App\Console\PurgeOldDataRollbackPoints;
use App\Console\PurgeOldBotInstances;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        clearstatcache();
        // purge old servers
        $schedule->call(new PurgeOldServers)->everyMinute();

        // purge old discord instances
        $schedule->call(new PurgeOldBotInstances)->everyMinute();

        // verified user count
        $schedule->call(new VerifiedUserCount)->daily();

        // servers & player count
        $schedule->call(new ActiveServerCount)->everyMinute();

        // punishment expiry
        $schedule->call(new PunishmentExpiry)->everyMinute();

        // delete week-old rollback points and create new ones
        $schedule->call(new PurgeOldDataRollbackPoints)->daily();
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
