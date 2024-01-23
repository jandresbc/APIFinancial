<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\UnlockLock;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        UnlockLock::class,
		Commands\UpdateDocumentTypeCommand::class,
		Commands\UpdateSellersCommand::class,
		Commands\InvoicesTemporalCommand::class,
		Commands\UpdatePaymentTypesCommand::class,
		Commands\contact_flows::class,
    ];

    /**
     * Define the application's command schedule.
     *
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $filePath = '/var/www/html/api/lock.log';
         $schedule->command('unlock:lock')->cron('*/2 * * * *')->sendOutputTo($filePath);
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
