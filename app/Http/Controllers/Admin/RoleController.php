<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\Role as RoleName;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role elevation is always a deliberate, audited action by someone holding `roles.manage`.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::with('permissions')->withCount('users')->orderBy('id')->get(),
            'permissions' => Permission::orderBy('name')->get()->groupBy(fn ($p) => ucfirst(explode('.', $p->name)[0])),
            'descriptions' => RolesAndPermissionsSeeder::PERMISSIONS,
        ]);
    }

    public function update(Request $request, Role $role, AuditLogger $audit): RedirectResponse
    {
        abort_if($role->name === RoleName::SuperAdmin->value, 422, 'Super Admin always has full access.');

        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => [Rule::exists('permissions', 'name')],
        ]);

        $before = $role->permissions->pluck('name')->sort()->values()->all();
        $role->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $audit->log(AuditAction::RoleChange, null, "Permissions of role {$role->name} updated", ['permissions' => $before], ['permissions' => collect($data['permissions'] ?? [])->sort()->values()->all()]);

        return back()->with('status', 'Permissions updated for '.(RoleName::tryFrom($role->name)?->label() ?? $role->name).'.');
    }

    public function assign(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => [Rule::exists('roles', 'name')],
        ]);
        $roles = $data['roles'] ?? [];

        if (! $request->user()->isSuperAdmin()) {
            // Only a Super Admin may grant or remove Super Admin.
            abort_if(in_array(RoleName::SuperAdmin->value, $roles, true) !== $user->hasRole(RoleName::SuperAdmin->value), 403, 'Only a Super Admin can change Super Admin access.');
        }

        $removingLastSuperAdmin = $user->hasRole(RoleName::SuperAdmin->value)
            && ! in_array(RoleName::SuperAdmin->value, $roles, true)
            && User::role(RoleName::SuperAdmin->value)->count() <= 1;
        abort_if($removingLastSuperAdmin, 422, 'There must always be at least one Super Admin.');

        if ($roles === []) {
            $roles = [RoleName::User->value];
        }

        $before = $user->getRoleNames()->sort()->values()->all();
        $user->syncRoles($roles);
        $after = $user->getRoleNames()->sort()->values()->all();

        if ($before !== $after) {
            $audit->log(AuditAction::RoleChange, $user, "Roles of {$user->name}: ".implode(', ', $after), ['roles' => $before], ['roles' => $after]);
        }

        return back()->with('status', 'Roles updated.');
    }
}
