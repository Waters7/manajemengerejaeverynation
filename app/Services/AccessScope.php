<?php

namespace App\Services;

use App\Enums\LifeGroupRole;
use App\Enums\MemberStatus;
use App\Enums\PrayerVisibility;
use App\Enums\Role;
use App\Models\DisciplerRelationship;
use App\Models\LifeGroup;
use App\Models\LifeGroupMember;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Central place that narrows queries to what a user is allowed to see.
 *
 * - Super Admin / Pastor: whole church.
 * - Campus Ministry: people, groups, events and classes of their assigned campuses.
 * - Leader: their own LifeGroup(s) and their discipleship downline.
 * - Ministry Coordinator: their own ministries.
 * - Welcome Team: newcomers and Get Involved requests.
 * - Everyone: records explicitly assigned to them for follow-up.
 */
class AccessScope
{
    /** @var array<string, mixed> */
    private array $memo = [];

    public function isChurchWide(User $user): bool
    {
        return $user->hasChurchWideAccess();
    }

    /** @return list<int> */
    public function campusIds(User $user): array
    {
        return $this->remember("campus.{$user->id}", fn () => $user->hasRole(Role::CampusMinistry->value)
            ? $user->campuses()->pluck('campuses.id')->all()
            : []);
    }

    public function profileId(User $user): ?int
    {
        return $this->remember("profile.{$user->id}", fn () => $user->profile()->value('id'));
    }

    /** LifeGroups this user leads or co-leads. @return list<int> */
    public function ledLifeGroupIds(User $user): array
    {
        return $this->remember("led.{$user->id}", function () use ($user) {
            $profileId = $this->profileId($user);
            if (! $profileId) {
                return [];
            }

            $direct = LifeGroup::where(fn ($q) => $q->where('leader_profile_id', $profileId)->orWhere('co_leader_profile_id', $profileId))
                ->pluck('id');
            $viaMembership = LifeGroupMember::where('profile_id', $profileId)
                ->where('status', 'active')
                ->whereIn('role', [LifeGroupRole::Leader->value, LifeGroupRole::CoLeader->value])
                ->pluck('life_group_id');

            return $direct->merge($viaMembership)->unique()->values()->all();
        });
    }

    /** Whole discipleship downline of this user (disciples, their disciples, …). @return list<int> */
    public function discipleIds(User $user): array
    {
        return $this->remember("disciples.{$user->id}", function () use ($user) {
            $profileId = $this->profileId($user);

            return $profileId ? $this->downline($profileId) : [];
        });
    }

    /** @return list<int> */
    public function downline(int $profileId, int $maxDepth = 12): array
    {
        $seen = [];
        $frontier = [$profileId];

        for ($depth = 0; $depth < $maxDepth && $frontier !== []; $depth++) {
            $children = DisciplerRelationship::active()
                ->whereIn('discipler_profile_id', $frontier)
                ->pluck('disciple_profile_id')
                ->diff($seen)
                ->diff([$profileId])
                ->all();
            $seen = array_merge($seen, $children);
            $frontier = $children;
        }

        return array_values(array_unique($seen));
    }

    /** @return list<int> */
    public function ministryIds(User $user): array
    {
        return $this->remember("ministries.{$user->id}", fn () => $user->coordinatedMinistries()->pluck('id')->all());
    }

    // ── Query scopes ───────────────────────────────────────────────

    public function profiles(Builder $query, User $user): Builder
    {
        if ($this->isChurchWide($user)) {
            return $query;
        }

        $campusIds = $this->campusIds($user);
        $groupIds = $this->ledLifeGroupIds($user);
        $discipleIds = $this->discipleIds($user);
        $ministryIds = $this->ministryIds($user);
        $isWelcomeTeam = $user->hasRole(Role::WelcomeTeam->value);

        return $query->where(function (Builder $q) use ($user, $campusIds, $groupIds, $discipleIds, $ministryIds, $isWelcomeTeam) {
            $q->whereHas('followUpTasks', fn ($t) => $t->where('assigned_to', $user->id))
                ->orWhereHas('newcomer', fn ($n) => $n->where('assigned_to', $user->id));

            if ($campusIds !== []) {
                $q->orWhereIn('campus_id', $campusIds)
                    ->orWhereHas('lifeGroups', fn ($g) => $g->whereIn('life_groups.campus_id', $campusIds));
            }
            if ($groupIds !== []) {
                $q->orWhereHas('lifeGroupMemberships', fn ($m) => $m->whereIn('life_group_id', $groupIds));
            }
            if ($discipleIds !== []) {
                $q->orWhereIn('id', $discipleIds);
            }
            if ($ministryIds !== []) {
                $q->orWhereHas('ministryMemberships', fn ($m) => $m->whereIn('ministry_id', $ministryIds));
            }
            if ($isWelcomeTeam) {
                $q->orWhereIn('member_status', [MemberStatus::Visitor->value, MemberStatus::Newcomer->value]);
            }
        });
    }

    public function canSeeProfile(User $user, Profile $profile): bool
    {
        return $this->isChurchWide($user)
            || $this->profileId($user) === $profile->id
            || $this->profiles(Profile::query(), $user)->whereKey($profile->id)->exists();
    }

    public function involvementRequests(Builder $query, User $user): Builder
    {
        if ($this->isChurchWide($user) || $user->hasRole(Role::WelcomeTeam->value)) {
            return $query;
        }
        $campusIds = $this->campusIds($user);
        $ministryIds = $this->ministryIds($user);

        return $query->where(function (Builder $q) use ($user, $campusIds, $ministryIds) {
            $q->where('assigned_to', $user->id);
            if ($campusIds !== []) {
                $q->orWhereIn('campus_id', $campusIds);
            }
            if ($ministryIds !== []) {
                $q->orWhereHas('ministries', fn ($m) => $m->whereIn('ministries.id', $ministryIds));
            }
        });
    }

    public function lifeGroups(Builder $query, User $user): Builder
    {
        if ($this->isChurchWide($user)) {
            return $query;
        }
        $campusIds = $this->campusIds($user);
        $groupIds = $this->ledLifeGroupIds($user);

        return $query->where(function (Builder $q) use ($campusIds, $groupIds) {
            $q->whereIn('life_groups.id', $groupIds ?: [0]);
            if ($campusIds !== []) {
                $q->orWhereIn('life_groups.campus_id', $campusIds);
            }
        });
    }

    public function canManageLifeGroup(User $user, LifeGroup $group): bool
    {
        return $this->isChurchWide($user)
            || in_array($group->id, $this->ledLifeGroupIds($user), true)
            || ($group->campus_id && in_array($group->campus_id, $this->campusIds($user), true));
    }

    public function joinRequests(Builder $query, User $user): Builder
    {
        if ($this->isChurchWide($user) || $user->hasRole(Role::WelcomeTeam->value)) {
            return $query;
        }

        return $query->whereIn('life_group_id', $this->lifeGroups(LifeGroup::query(), $user)->select('life_groups.id'));
    }

    public function classBatches(Builder $query, User $user): Builder
    {
        if ($this->isChurchWide($user)) {
            return $query;
        }
        $campusIds = $this->campusIds($user);
        if ($campusIds !== []) {
            return $query->whereIn('campus_id', $campusIds);
        }

        // Leaders may look at class schedules (read only) to encourage their people.
        return $user->can('classes.view') ? $query : $query->whereRaw('1 = 0');
    }

    public function events(Builder $query, User $user): Builder
    {
        if ($this->isChurchWide($user)) {
            return $query;
        }
        $campusIds = $this->campusIds($user);

        return $query->where(function (Builder $q) use ($campusIds, $user) {
            $q->whereIn('campus_id', $campusIds ?: [0])->orWhere('created_by', $user->id);
        });
    }

    public function ministries(Builder $query, User $user): Builder
    {
        if ($this->isChurchWide($user)) {
            return $query;
        }

        return $query->whereIn('ministries.id', $this->ministryIds($user) ?: [0]);
    }

    public function volunteerApplications(Builder $query, User $user): Builder
    {
        if ($this->isChurchWide($user)) {
            return $query;
        }
        $ministryIds = $this->ministryIds($user);

        return $query->whereHas('ministries', fn ($m) => $m->whereIn('ministries.id', $ministryIds ?: [0]));
    }

    public function prayerRequests(Builder $query, User $user): Builder
    {
        if ($this->isChurchWide($user)) {
            return $query;
        }
        $groupIds = $this->lifeGroups(LifeGroup::query(), $user)->pluck('life_groups.id')->all();
        $prayerTeam = $user->can('prayer.team');

        return $query->where(function (Builder $q) use ($groupIds, $prayerTeam, $user) {
            $q->where('assigned_to', $user->id)
                ->orWhere(fn ($q) => $q->where('visibility', PrayerVisibility::LifegroupLeader->value)->whereIn('life_group_id', $groupIds ?: [0]));
            if ($prayerTeam) {
                $q->orWhereIn('visibility', [PrayerVisibility::PrayerTeam->value, PrayerVisibility::LifegroupLeader->value]);
            }
        });
    }

    public function followUpTasks(Builder $query, User $user): Builder
    {
        if ($this->isChurchWide($user)) {
            return $query;
        }

        return $query->where('assigned_to', $user->id);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $resolver
     * @return T
     */
    private function remember(string $key, callable $resolver): mixed
    {
        return $this->memo[$key] ??= $resolver();
    }

    public function forget(): void
    {
        $this->memo = [];
    }
}
