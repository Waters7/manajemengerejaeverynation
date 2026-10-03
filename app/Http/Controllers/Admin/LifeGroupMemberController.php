<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LifeGroupRole;
use App\Http\Controllers\Controller;
use App\Models\LifeGroup;
use App\Models\LifeGroupMember;
use App\Models\Profile;
use App\Services\AccessScope;
use App\Services\LifeGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LifeGroupMemberController extends Controller
{
    public function store(Request $request, LifeGroup $lifeGroup, LifeGroupService $service, AccessScope $scope): RedirectResponse
    {
        $this->authorize('update', $lifeGroup);
        $data = $request->validate([
            'profile_id' => ['required', Rule::exists('profiles', 'id')],
            'role' => ['required', Rule::enum(LifeGroupRole::class)],
        ]);

        $role = LifeGroupRole::from($data['role']);
        // Appointing (co-)leaders is reserved for pastors / campus ministry.
        if (in_array($role, [LifeGroupRole::Leader, LifeGroupRole::CoLeader], true) && ! $scope->isChurchWide($request->user()) && $scope->campusIds($request->user()) === []) {
            $role = LifeGroupRole::Member;
        }

        $profile = Profile::findOrFail($data['profile_id']);
        $service->addMember($lifeGroup, $profile, $role);

        return back()->with('status', "{$profile->displayName()} added to {$lifeGroup->name}.");
    }

    public function update(Request $request, LifeGroupMember $membership, AccessScope $scope): RedirectResponse
    {
        $this->authorize('update', $membership->lifeGroup);
        $data = $request->validate(['role' => ['required', Rule::enum(LifeGroupRole::class)]]);

        $role = LifeGroupRole::from($data['role']);
        $leaderRoles = [LifeGroupRole::Leader, LifeGroupRole::CoLeader];
        if ((in_array($role, $leaderRoles, true) || in_array($membership->role, $leaderRoles, true))
            && ! $scope->isChurchWide($request->user()) && $scope->campusIds($request->user()) === []) {
            return back()->with('error', 'Only a pastor can appoint or change LifeGroup leaders.');
        }

        $membership->update(['role' => $role]);

        return back()->with('status', 'Role updated.');
    }

    public function destroy(LifeGroupMember $membership, LifeGroupService $service): RedirectResponse
    {
        $this->authorize('update', $membership->lifeGroup);
        abort_if($membership->role === LifeGroupRole::Leader, 422, 'Change the group leader from the LifeGroup settings first.');

        $service->removeMember($membership);

        return back()->with('status', 'Membership updated.');
    }
}
