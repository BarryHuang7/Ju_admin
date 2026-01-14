<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * 注册应用中的命令
     *
     * @var array
     */
    protected $commands = [
        // swoole websocket
        \App\Console\Commands\Swoole::class,
    ];

    /**
     * 定义命令调度
     */
    protected function schedule(Schedule $schedule)
    {
        // 定时任务
        // $schedule->command('inspire')->hourly();
    }

    /**
     * 注册命令
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}