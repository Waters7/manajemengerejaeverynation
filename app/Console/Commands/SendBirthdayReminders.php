<?php

namespace App\Console\Commands;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Notifications\TeamAlert;
use App\Services\AccessScope;
use App\Services\BirthdayService;
use Illuminate\Console\Command;

/**
 * Reminds pastors / leaders of today's birthdays among the people they care for.
 */
class SendBirthdayReminders extends Command
{
    protected $signature = 'church:birthday-reminders';

    protected $description = "Notify the ministry team about today's birthdays";

    public function handle(BirthdayService $birthdays, AccessScope $scope): int
    {
        $sent = 0;

        User::permission('birthdays.view')
            ->where('account_status', AccountStatus::Active->value)
            ->each(function (User $user) use ($birthdays, $scope, &$sent) {
                $scope->forget();
                $today = $birthdays->upcoming($user, 'today');
                if ($today->isEmpty()) {
                    return;
                }

                $user->notify(new TeamAlert(
                    'birthday',
                    $today->count() === 1 ? 'A birthday today 🎉' : "{$today->count()} birthdays today 🎉",
                    $today->map(fn ($row) => $row['profile']->displayName())->implode(', ').' — send a greeting!',
                    route('admin.birthdays.index', ['range' => 'today']),
                ));
                $sent++;
            });

        $this->info("Birthday reminders sent to {$sent} team member(s).");

        return self::SUCCESS;
    }
}
