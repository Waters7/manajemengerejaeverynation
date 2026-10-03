<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Automation (run `php artisan schedule:work`, or a cron calling schedule:run)
|--------------------------------------------------------------------------
*/
Schedule::command('church:publish-scheduled')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('church:birthday-reminders')->dailyAt('06:00');
Schedule::command('church:follow-up-digest')->weekdays()->at('07:00');
Schedule::command('queue:prune-failed --hours=168')->weekly();

// Shared hosting without a long-running worker: drain the queue once a minute.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => config('queue.run_via_scheduler'));
