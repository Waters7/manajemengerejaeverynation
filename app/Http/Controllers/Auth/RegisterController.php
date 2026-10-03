<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Profile;
use App\Models\User;
use App\Notifications\TeamAlert;
use App\Services\PeopleService;
use App\Services\TeamNotifier;
use App\Services\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Public sign-up. New accounts are always role USER and "Pending Verification";
 * ministry roles are only ever granted manually by an authorised admin.
 */
class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, PeopleService $people, TeamNotifier $notifier): RedirectResponse
    {
        $user = DB::transaction(function () use ($request, $people) {
            $data = $request->validated();

            $user = User::create([
                'name' => $data['name'],
                'nickname' => $data['nickname'],
                'email' => $data['email'],
                'whatsapp' => WhatsApp::normalize($data['whatsapp']),
                'password' => $data['password'],
                'account_status' => AccountStatus::PendingVerification,
            ]);
            $user->assignRole(Role::User->value);

            // Link an existing person record (e.g. from a Connect Card) when it is not yet tied to an account.
            $profile = $people->findOrCreate([
                'full_name' => $data['name'],
                'nickname' => $data['nickname'],
                'whatsapp' => $data['whatsapp'],
                'email' => $data['email'],
            ]);
            if ($profile->user_id && $profile->user_id !== $user->id) {
                $profile = Profile::create([
                    'full_name' => $data['name'],
                    'nickname' => $data['nickname'],
                    'email' => $data['email'],
                    'member_status' => $profile->member_status,
                ]);
            }
            $profile->forceFill(['user_id' => $user->id])->save();

            return $user;
        });

        $notifier->toPermission('users.manage', new TeamAlert(
            'account',
            'New account waiting for verification',
            "{$user->name} just created an account.",
            route('admin.users.edit', $user),
        ));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('member.dashboard')->with('status', "Welcome, {$user->displayName()}! Akun kamu berhasil dibuat.");
    }
}
