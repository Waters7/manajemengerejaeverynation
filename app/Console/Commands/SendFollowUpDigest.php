<?php

namespace App\Console\Commands;

use App\Enums\AccountStatus;
use App\Models\FollowUpTask;
use App\Models\User;
use App\Notifications\TeamAlert;
use App\Services\AccessScope;
use App\Services\CareRadar;
use Illuminate\Console\Command;

/**
 * Morning digest: "N people would love a follow-up today".
 */
class SendFollowUpDigest extends Command
{
    protected $signature = 'church:follow-up-digest';

    protected $description = 'Send each ministry team member a digest of people who need follow-up';

    public function handle(CareRadar $radar, AccessScope $scope): int
    {
        $sent = 0;

        User::permission('followups.view')
            ->where('account_status', AccountStatus::Active->value)
            ->each(function (User $user) use ($radar, $scope, &$sent) {
                $scope->forget();
                $sections = $radar->sections($user, 0);
                $dueToday = FollowUpTask::pending()->where('assigned_to', $user->id)->whereDate('due_date', '<=', today())->count();
                $total = $sections->sum('count');

                if ($total === 0 && $dueToday === 0) {
                    return;
                }

                $lines = $sections->map(fn ($s) => "{$s['label']}: {$s['count']}")->implode(' · ');
                $user->notify(new TeamAlert(
                    'digest',
                    $dueToday ? "{$dueToday} follow-up(s) due today" : 'People waiting for a caring touch',
                    $lines,
                    route('admin.follow-ups.index'),
                    sendMail: $dueToday > 0,
                ));
                $sent++;
            });

        $this->info("Digest sent to {$sent} team member(s).");

        return self::SUCCESS;
    }
}
