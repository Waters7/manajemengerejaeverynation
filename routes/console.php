<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Automation (cron every minute: `php artisan schedule:run`)
|--------------------------------------------------------------------------
|
| Tasks run in-process through Artisan::call() instead of Schedule::command(),
| because shared hosting (e.g. cPanel / CloudLinux) often disables proc_open,
| which the scheduler needs to start commands as separate processes.
|
*/

$artisan = fn (string $command, array $parameters = []) => fn () => Artisan::call($command, $parameters);

Schedule::call($artisan('church:publish-scheduled'))
    ->name('church:publish-scheduled')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::call($artisan('church:birthday-reminders'))
    ->name('church:birthday-reminders')
    ->dailyAt('06:00');

Schedule::call($artisan('church:follow-up-digest'))
    ->name('church:follow-up-digest')
    ->weekdays()
    ->at('07:00');

Schedule::call($artisan('store:cancel-unpaid'))
    ->name('store:cancel-unpaid')
    ->hourly()
    ->withoutOverlapping();

Schedule::call($artisan('queue:prune-failed', ['--hours' => 168]))
    ->name('queue:prune-failed')
    ->weekly();

// Shared hosting without a long-running worker: drain the queue once a minute.
Schedule::call($artisan('queue:work', ['--stop-when-empty' => true, '--max-time' => 50, '--tries' => 3]))
    ->name('queue:work')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => config('queue.run_via_scheduler'));
