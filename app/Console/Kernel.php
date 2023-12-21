<?php

namespace App\Console;

use App\Jobs\CloseAbandonedCartsJob;
use App\Jobs\CloseAbandonedOrdersJob;
use App\Jobs\Imap\ImapCheckMailJob;
use App\Jobs\Push\ThrowedCartJob;
use App\Jobs\RemoveExpiredActualsJob;
use App\Jobs\SDG\SDGParseAnswerJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule (Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->job(new ImapCheckMailJob)->everyMinute();
        $schedule->job(new CloseAbandonedOrdersJob)->dailyAt('23:55');
        $schedule->job(new CloseAbandonedCartsJob)->dailyAt('23:57');
        $schedule->job(new SDGParseAnswerJob)->everyMinute();
        /*$schedule->job(new ImapSendOrdersToProviderJob)->dailyAt(
            implode(':', [
                str_pad(CartService::$time_finish[0], 2, '0', STR_PAD_LEFT),
                str_pad(CartService::$time_finish[1], 2, '0', STR_PAD_LEFT)
            ])
        );*/
        $schedule->job(new ThrowedCartJob)->everyFiveMinutes();
        $schedule->job(new RemoveExpiredActualsJob)->dailyAt('23:59');

        $schedule->command('telescope:prune --hours=96')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands ()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
