<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\MemberStatus;
use App\Enums\Role;
use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'nickname', 'email', 'whatsapp', 'password', 'account_status', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'account_status' => AccountStatus::class,
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function campuses(): BelongsToMany
    {
        return $this->belongsToMany(Campus::class);
    }

    public function coordinatedMinistries(): HasMany
    {
        return $this->hasMany(Ministry::class, 'coordinator_id');
    }

    public function followUpTasks(): HasMany
    {
        return $this->hasMany(FollowUpTask::class, 'assigned_to');
    }

    /** Every account is backed by a person record; create it lazily for older accounts. */
    public function ensureProfile(): Profile
    {
        if ($this->profile) {
            return $this->profile;
        }

        $profile = $this->profile()->create([
            'full_name' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'whatsapp' => $this->whatsapp,
            'member_status' => MemberStatus::Newcomer,
        ]);
        $this->setRelation('profile', $profile);

        return $profile;
    }

    public function displayName(): string
    {
        return $this->nickname ?: $this->name;
    }

    public function isActive(): bool
    {
        return $this->account_status === AccountStatus::Active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SuperAdmin->value);
    }

    /** Pastors and super admins see the whole church. */
    public function hasChurchWideAccess(): bool
    {
        return $this->hasAnyRole([Role::SuperAdmin->value, Role::Pastor->value]);
    }

    /** Anyone holding a ministry role (anything beyond USER) may enter the admin area. */
    public function canAccessAdmin(): bool
    {
        return $this->isActive() && $this->can('admin.access');
    }

    public function primaryRole(): ?Role
    {
        foreach (Role::cases() as $role) {
            if ($this->hasRole($role->value)) {
                return $role;
            }
        }

        return null;
    }
}
