<x-layouts.admin title="Leadership Pipeline">
    <x-page-header title="Leadership pipeline" description="Developing the next generation of leaders. Pastors make the final decision — the system never appoints a leader automatically.">
        @can('leadership.manage')
            <x-modal name="nominate" title="Recommend a potential leader">
                <x-slot:trigger><button type="button" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Recommend</button></x-slot:trigger>
                <form method="POST" action="{{ route('admin.leadership.store') }}" class="space-y-4">
                    @csrf
                    <x-form.select name="profile_id" label="Person" :options="$people" placeholder="Choose…" required />
                    <x-form.textarea name="recommendation" label="Leader recommendation" rows="3" />
                    <button class="btn btn-primary w-full">Add to pipeline</button>
                </form>
            </x-modal>
        @endcan
    </x-page-header>

    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach ($stages as $stage)
            @php $items = $candidates->get($stage->value, collect()); @endphp
            <section class="w-80 shrink-0">
                <div class="mb-3 flex items-center justify-between px-1">
                    <h2 class="text-sm font-extrabold tracking-wider uppercase">{{ $stage->label() }}</h2>
                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-bold">{{ $items->count() }}</span>
                </div>
                <div class="space-y-3">
                    @forelse ($items as $candidate)
                        @php $snap = $snapshots[$candidate->id]; @endphp
                        <article class="card p-4" x-data="{ open: false }">
                            <a href="{{ route('admin.members.show', $candidate->profile) }}" class="flex items-center gap-3 font-extrabold hover:text-brand"><x-avatar :profile="$candidate->profile" size="size-9" /> {{ $candidate->profile->full_name }}</a>
                            <dl class="mt-3 space-y-1 text-xs">
                                <div class="flex justify-between gap-2"><dt class="text-muted">LifeGroup</dt><dd class="text-right font-semibold">{{ $snap['lifegroup'] ?? '—' }}</dd></div>
                                <div class="flex justify-between gap-2"><dt class="text-muted">Stage</dt><dd class="font-semibold">{{ $snap['stage'] ?? '—' }}</dd></div>
                                <div class="flex justify-between gap-2"><dt class="text-muted">Leadership 113</dt><dd class="font-semibold">{{ $snap['l113'] }}</dd></div>
                                <div class="flex justify-between gap-2"><dt class="text-muted">Leadership 215</dt><dd class="font-semibold">{{ $snap['l215'] }}</dd></div>
                                <div class="flex justify-between gap-2"><dt class="text-muted">Current disciples</dt><dd class="font-semibold">{{ $snap['disciples'] }}</dd></div>
                            </dl>
                            <button type="button" x-on:click="open = !open" class="mt-3 text-xs font-bold text-brand">Details & move</button>
                            <div x-show="open" x-cloak class="mt-3 space-y-3 border-t border-line pt-3 text-xs">
                                <p><span class="font-bold">Completed:</span> {{ implode(', ', $snap['completed']) ?: '—' }}</p>
                                @if ($candidate->recommendation)<p><span class="font-bold">Recommendation{{ $candidate->recommender ? ' ('.$candidate->recommender->name.')' : '' }}:</span> {{ $candidate->recommendation }}</p>@endif
                                @if ($candidate->decider)<p class="text-muted">Decision by {{ $candidate->decider->name }} · {{ $candidate->decided_at?->translatedFormat('j M Y') }}</p>@endif
                                @can('leadership.manage')
                                    <form method="POST" action="{{ route('admin.leadership.update', $candidate) }}" class="space-y-2">
                                        @csrf @method('PATCH')
                                        <select name="stage" class="input !py-1.5 text-xs">
                                            @foreach ($stages as $option)
                                                @php $needsApproval = in_array($option, [\App\Enums\LeadershipStage::Approved, \App\Enums\LeadershipStage::ActiveLeader], true); @endphp
                                                <option value="{{ $option->value }}" @selected($option === $candidate->stage) @disabled($needsApproval && ! $canApprove)>{{ $option->label() }}{{ $needsApproval && ! $canApprove ? ' (pastor)' : '' }}</option>
                                            @endforeach
                                        </select>
                                        <textarea name="notes" rows="2" class="input text-xs" placeholder="Notes">{{ $candidate->notes }}</textarea>
                                        <button class="btn btn-dark btn-sm w-full">Save</button>
                                    </form>
                                @endcan
                            </div>
                        </article>
                    @empty
                        <p class="rounded-2xl border border-dashed border-line p-4 text-center text-xs text-muted">No one here yet</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</x-layouts.admin>
