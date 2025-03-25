<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * These schedules are used to run commands at specific times or intervals.
     */
    protected function schedule(Schedule $schedule)
    {
        // Verificar assinaturas expiradas nos últimos 6 minutos, a cada 5 minutos
        $schedule->command('subscriptions:check-expired --recently-expired=6')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();
                 
        // Processar novas assinaturas nos últimos 6 minutos, a cada 5 minutos
        $schedule->command('subscriptions:process-new --recently-created=6')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();
                 
        // Processar a fila do Redis a cada 5 minutos
        $schedule->command('queue:work redis --queue=subscriptions --tries=3 --max-jobs=50 --stop-when-empty')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    protected $commands = [
        Commands\CheckExpiredSubscriptionsCommand::class,
        Commands\ProcessNewSubscriptionsCommand::class,
    ];
} 