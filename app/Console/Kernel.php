<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // 'App\Console\Commands\ExecSmsSend',
        'App\Console\Commands\RebuildFollowCache',
        'App\Console\Commands\InitUserData',
        'App\Console\Commands\Combination',
        'App\Console\Commands\SendSms',
        'App\Console\Commands\SendEmail',
        'App\Console\Commands\IpSearch',
        'App\Console\Commands\CheckRedPackTimeOut',
        'App\Console\Commands\SetCoinPrice',
        'App\Console\Commands\RedPackTop',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
