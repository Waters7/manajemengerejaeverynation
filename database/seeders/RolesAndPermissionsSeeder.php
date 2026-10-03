<?php

namespace Database\Seeders;

use App\Enums\Role as RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Implements the permission matrix in docs/03-permissions-and-workflows.md.
 * Data scoping (own LifeGroup, own campus, own ministry) is applied by App\Services\AccessScope.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /** @var array<string, string> permission => description */
    public const PERMISSIONS = [
        'admin.access' => 'Enter the ministry dashboard',
        'members.view' => 'View people (scoped)',
        'members.manage' => 'Create and edit people (scoped)',
        'newcomers.view' => 'View newcomers',
        'newcomers.manage' => 'Manage newcomer journey',
        'involvement.view' => 'View Get Involved requests',
        'involvement.manage' => 'Assign and update Get Involved requests',
        'followups.view' => 'View follow-up tasks',
        'followups.manage' => 'Create and complete follow-up tasks',
        'discipleship.view' => 'View discipleship journeys',
        'discipleship.manage' => 'Update discipleship progress and meetings',
        'curriculum.manage' => 'Configure stages, programs, books and chapters',
        'classes.view' => 'View class batches',
        'classes.manage' => 'Manage class batches, sessions and attendance',
        'leadership.view' => 'View leadership pipeline',
        'leadership.manage' => 'Recommend and move leadership candidates',
        'leadership.approve' => 'Approve leaders (final decision)',
        'lifegroups.view' => 'View LifeGroups',
        'lifegroups.manage' => 'Manage LifeGroups, members, meetings and attendance',
        'lifegroups.requests' => 'Handle LifeGroup join requests',
        'ministries.view' => 'View ministries',
        'ministries.manage' => 'Manage ministries, roles and serving schedule',
        'volunteers.manage' => 'Review volunteer applications',
        'campus.view' => 'View campus ministry',
        'campus.manage' => 'Manage campuses',
        'events.manage' => 'Manage events, registrations and check-in',
        'content.manage' => 'Manage devotionals, sermons, gallery, pages and homepage',
        'prayer.view' => 'View prayer requests (scoped by visibility)',
        'prayer.manage' => 'Update prayer requests',
        'prayer.team' => 'Prayer Team access',
        'pastoral.view' => 'View pastoral care (restricted)',
        'pastoral.manage' => 'Manage pastoral care (restricted)',
        'announcements.manage' => 'Manage announcements',
        'birthdays.view' => 'View birthdays',
        'reports.view' => 'View reports',
        'reports.export' => 'Export reports',
        'users.manage' => 'Manage user accounts',
        'roles.manage' => 'Assign roles and permissions',
        'settings.manage' => 'Manage site settings and templates',
        'media.manage' => 'Manage media library',
        'audit.view' => 'View audit logs',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(self::PERMISSIONS) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $matrix = [
            RoleName::SuperAdmin->value => array_keys(self::PERMISSIONS),
            RoleName::Pastor->value => array_values(array_diff(array_keys(self::PERMISSIONS), [
                'users.manage', 'roles.manage', 'settings.manage', 'media.manage', 'audit.view', 'ministries.manage',
            ])),
            RoleName::CampusMinistry->value => [
                'admin.access', 'members.view', 'members.manage', 'newcomers.view', 'newcomers.manage', 'involvement.view',
                'involvement.manage', 'followups.view', 'followups.manage', 'discipleship.view', 'discipleship.manage',
                'classes.view', 'classes.manage', 'leadership.view', 'lifegroups.view', 'lifegroups.manage', 'lifegroups.requests',
                'ministries.view', 'campus.view', 'campus.manage', 'events.manage', 'prayer.view', 'prayer.manage',
                'birthdays.view', 'reports.view', 'reports.export',
            ],
            RoleName::Leader->value => [
                'admin.access', 'members.view', 'members.manage', 'newcomers.view', 'involvement.view', 'followups.view',
                'followups.manage', 'discipleship.view', 'discipleship.manage', 'classes.view', 'leadership.view',
                'leadership.manage', 'lifegroups.view', 'lifegroups.manage', 'lifegroups.requests', 'prayer.view',
                'prayer.manage', 'birthdays.view',
            ],
            RoleName::MinistryCoordinator->value => [
                'admin.access', 'members.view', 'involvement.view', 'followups.view', 'followups.manage', 'ministries.view',
                'ministries.manage', 'volunteers.manage', 'birthdays.view',
            ],
            RoleName::WelcomeTeam->value => [
                'admin.access', 'members.view', 'newcomers.view', 'newcomers.manage', 'involvement.view', 'involvement.manage',
                'followups.view', 'followups.manage', 'lifegroups.view', 'lifegroups.requests',
            ],
            RoleName::User->value => [],
        ];

        foreach ($matrix as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
