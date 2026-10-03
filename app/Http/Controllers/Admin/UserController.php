<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\Ministry;
use App\Models\User;
use App\Rules\IndonesianPhone;
use App\Rules\LoginIdentifier;
use App\Services\AuditLogger;
use App\Services\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with(['roles', 'profile'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->string('q').'%')->orWhere('email', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('role'), fn ($q) => $q->role($request->string('role')->value()))
            ->when($request->filled('status'), fn ($q) => $q->where('account_status', $request->string('status')))
            ->orderByRaw("account_status = 'pending_verification' desc")
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('id')->pluck('name', 'name')->map(fn ($r) => \App\Enums\Role::tryFrom($r)?->label() ?? $r),
            'statuses' => AccountStatus::options(),
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'account' => $user->load(['roles', 'campuses', 'profile', 'coordinatedMinistries']),
            'roles' => Role::orderBy('id')->get(),
            'statuses' => AccountStatus::options(),
            'campuses' => Campus::orderBy('name')->pluck('name', 'id'),
            'ministries' => Ministry::active()->pluck('name', 'id'),
            'logs' => AuditLog::where('user_id', $user->id)->latest('created_at')->limit(15)->get(),
        ]);
    }

    public function update(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'nickname' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'string', 'max:190', new LoginIdentifier, Rule::unique('users', 'email')->ignore($user->id)],
            'whatsapp' => ['nullable', 'string', new IndonesianPhone],
            'account_status' => ['required', Rule::enum(AccountStatus::class)],
            'campuses' => ['array'],
            'campuses.*' => [Rule::exists('campuses', 'id')],
            'ministries' => ['array'],
            'ministries.*' => [Rule::exists('ministries', 'id')],
        ]);

        abort_if($user->is($request->user()) && $data['account_status'] !== AccountStatus::Active->value, 422, 'You cannot deactivate your own account.');

        $wasStatus = $user->account_status;
        $user->update([
            'name' => $data['name'],
            'nickname' => $data['nickname'] ?? null,
            'email' => $data['email'],
            'whatsapp' => WhatsApp::normalize($data['whatsapp'] ?? null),
            'account_status' => $data['account_status'],
            'email_verified_at' => $user->email_verified_at ?? ($data['account_status'] === AccountStatus::Active->value ? now() : null),
        ]);

        $campusChanges = $user->campuses()->sync($data['campuses'] ?? []);
        if (array_filter($campusChanges)) {
            $audit->log(AuditAction::Assign, $user, "Campus scope for {$user->name}: ".Campus::whereIn('id', $data['campuses'] ?? [])->pluck('name')->implode(', '));
        }

        // Ministry coordinator scope: coordinators manage the ministries they coordinate.
        Ministry::where('coordinator_id', $user->id)->whereNotIn('id', $data['ministries'] ?? [])->update(['coordinator_id' => null]);
        Ministry::whereIn('id', $data['ministries'] ?? [])->update(['coordinator_id' => $user->id]);

        if ($wasStatus !== $user->account_status && $user->account_status === AccountStatus::Active) {
            $audit->log(AuditAction::Approve, $user, "Account of {$user->name} verified");
        }

        return back()->with('status', 'Account updated.');
    }
}
