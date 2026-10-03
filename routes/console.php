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
