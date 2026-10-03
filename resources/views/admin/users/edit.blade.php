<x-layouts.admin :title="$account->name">
    <x-page-header :title="$account->name" :description="$account->email" :back="route('admin.users.index')">
        @if ($account->profile)<a href="{{ route('admin.members.show', $account->profile) }}" class="btn btn-outline btn-sm">Person profile</a>@endif
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.users.update', $account) }}" class="card card-pad space-y-5 lg:col-span-2">
            @csrf @method('PUT')
            <h2 class="font-extrabold uppercase">Account</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="name" label="Name" :value="$account->name" required />
                <x-form.input name="nickname" label="Nickname" :value="$account->nickname" />
                <x-form.input name="email" type="email" label="Email" :value="$account->email" required />
                <x-form.input name="whatsapp" label="WhatsApp" :value="\App\Services\WhatsApp::display($account->whatsapp)" />
                <x-form.select name="account_status" label="Account status" :options="$statuses" :value="$account->account_status" required />
            </div>

            @if ($account->hasRole(\App\Enums\Role::CampusMinistry->value) && $campuses->isNotEmpty())
                <fieldset>
                    <legend class="label">Campus scope (Campus Ministry)</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($campuses as $id => $name)
                            <label class="chip"><input type="checkbox" name="campuses[]" value="{{ $id }}" class="sr-only" @checked($account->campuses->contains('id', $id))> {{ $name }}</label>
                        @endforeach
                    </div>
                </fieldset>
            @else
                @foreach ($account->campuses as $campus)<input type="hidden" name="campuses[]" value="{{ $campus->id }}">@endforeach
            @endif

            @if ($account->hasRole(\App\Enums\Role::MinistryCoordinator->value))
                <fieldset>
                    <legend class="label">Coordinates ministries</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($ministries as $id => $name)
                            <label class="chip"><input type="checkbox" name="ministries[]" value="{{ $id }}" class="sr-only" @checked($account->coordinatedMinistries->contains('id', $id))> {{ $name }}</label>
                        @endforeach
                    </div>
                </fieldset>
            @else
                @foreach ($account->coordinatedMinistries as $ministry)<input type="hidden" name="ministries[]" value="{{ $ministry->id }}">@endforeach
            @endif

            <button class="btn btn-primary">Save account</button>
        </form>

        <div class="space-y-6">
            @can('roles.manage')
                <form method="POST" action="{{ route('admin.users.roles.update', $account) }}" class="card card-pad space-y-3" data-confirm="Change roles for {{ $account->name }}? This is recorded in the audit log.">
                    @csrf @method('PUT')
                    <h2 class="font-extrabold uppercase">Roles</h2>
                    <p class="text-xs text-muted">Role elevation is never automatic. Every change is audited.</p>
                    @foreach ($roles as $role)
                        @php $enum = \App\Enums\Role::tryFrom($role->name); @endphp
                        @if ($role->name !== \App\Enums\Role::SuperAdmin->value || auth()->user()->isSuperAdmin())
                            <label class="choice !py-3">
                                <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="checkbox mt-0.5" @checked($account->hasRole($role->name))>
                                {{ $enum?->label() ?? $role->name }}
                            </label>
                        @endif
                    @endforeach
                    <button class="btn btn-dark btn-sm w-full">Save roles</button>
                </form>
            @endcan

            <div class="card card-pad">
                <h2 class="font-extrabold uppercase">Recent activity</h2>
                <ul class="mt-3 space-y-2 text-xs">
                    @forelse ($logs as $log)
                        <li><x-badge :value="$log->action" /> {{ $log->description }} <span class="text-muted">· {{ $log->created_at->diffForHumans() }}</span></li>
                    @empty
                        <li class="text-muted">No activity yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layouts.admin>
