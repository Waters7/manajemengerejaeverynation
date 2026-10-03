<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BirthdayService
{
    public const RANGES = ['today' => 'Today', 'week' => 'Next 7 days', 'month' => 'This month'];

    public function __construct(
        private AccessScope $scope,
        private WhatsApp $whatsApp,
    ) {}

    /**
     * @return Collection<int, array{profile: Profile, date: Carbon, turning: int, lifegroup: ?string, link: ?string}>
     */
    public function upcoming(User $user, string $range = 'week'): Collection
    {
        $today = today();
        [$from, $to] = match ($range) {
            'today' => [$today, $today],
            'month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            default => [$today, $today->copy()->addDays(7)],
        };

        $months = collect([$from->month, $to->month])->unique()->all();

        return $this->scope->profiles(Profile::query(), $user)
            ->with('activeLifeGroups')
            ->whereNotNull('birth_date')
            ->where(fn ($q) => collect($months)->each(fn ($m) => $q->orWhereMonth('birth_date', $m)))
            ->get()
            ->map(function (Profile $profile) use ($from) {
                $date = $profile->birth_date->copy()->year($from->year);
                if ($date->lt($from)) {
                    $date->addYear();
                }

                return ['profile' => $profile, 'date' => $date];
            })
            ->filter(fn ($row) => $row['date']->betweenIncluded($from, $to))
            ->sortBy(fn ($row) => $row['date']->timestamp)
            ->map(fn ($row) => [
                'profile' => $row['profile'],
                'date' => $row['date'],
                'turning' => (int) $row['profile']->birth_date->diffInYears($row['date']),
                'lifegroup' => $row['profile']->activeLifeGroups->first()?->name,
                'link' => $this->whatsApp->templateLink($row['profile']->whatsapp, 'wa_template_birthday', [
                    'nickname' => $row['profile']->displayName(),
                    'name' => $row['profile']->full_name,
                    'sender' => $user->displayName(),
                ]),
            ])
            ->values();
    }
}
