<x-layouts.admin title="Prayer Request">
    <x-page-header :title="'Prayer for '.$prayer->requesterName()" :eyebrow="$prayer->created_at->translatedFormat('j F Y, H:i').' · '.ucfirst($prayer->source)" :back="route('admin.prayer-requests.index')">
        @if ($prayer->profile && ! $prayer->is_anonymous && auth()->user()->can('view', $prayer->profile))
            <a href="{{ route('admin.members.show', $prayer->profile) }}" class="btn btn-outline btn-sm">Person profile</a>
        @endif
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-pad">
                <div class="flex flex-wrap gap-2"><x-badge :value="$prayer->status" /><x-badge :value="$prayer->visibility" />@if ($prayer->lifeGroup)<x-badge color="gray">{{ $prayer->lifeGroup->name }}</x-badge>@endif</div>
                <p class="mt-5 text-lg leading-relaxed whitespace-pre-line text-slate-800">{{ $prayer->request }}</p>
                @if ($prayer->contact && ! $prayer->is_anonymous)<p class="mt-4 text-sm text-muted">Contact: {{ $prayer->contact }}</p>@endif
            </div>
            @if ($prayer->praise_report)
                <div class="card card-pad border-green-200 bg-green-50">
                    <p class="eyebrow !text-green-700">Praise report</p>
                    <p class="mt-2 whitespace-pre-line text-green-900">{{ $prayer->praise_report }}</p>
                </div>
            @endif
        </div>
        @can('prayer.manage')
            <form method="POST" action="{{ route('admin.prayer-requests.update', $prayer) }}" class="card card-pad h-fit space-y-4">
                @csrf @method('PATCH')
                <x-form.select name="status" label="Status" :options="$statuses" :value="$prayer->status" required />
                @if (auth()->user()->hasChurchWideAccess())
                    <x-form.select name="visibility" label="Visibility" :options="$visibilities" :value="$prayer->visibility" />
                @endif
                <x-form.select name="assigned_to" label="Follow-up by" :options="$team" :value="$prayer->assigned_to" placeholder="—" />
                <x-form.textarea name="praise_report" label="Praise report" :value="$prayer->praise_report" rows="3" />
                <button class="btn btn-primary w-full">Save</button>
            </form>
        @endcan
    </div>
</x-layouts.admin>
