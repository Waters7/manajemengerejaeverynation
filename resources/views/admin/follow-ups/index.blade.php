<x-layouts.admin title="Follow-Ups">
    <x-page-header title="Follow-ups" description="Caring for people one conversation at a time.">
        <x-modal name="new-task" title="New follow-up">
            <x-slot:trigger><button type="button" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> New follow-up</button></x-slot:trigger>
            <form method="POST" action="{{ route('admin.follow-ups.store') }}" class="space-y-4">
                @csrf
                <x-form.input name="title" label="What needs to happen?" required />
                <x-form.select name="category" label="Category" :options="$categories" value="general" />
                <x-form.select name="assigned_to" label="Assign to" :options="$team" :value="auth()->id()" />
                <x-form.input name="due_date" type="date" label="Due date" :value="today()->addDays(3)" />
                <x-form.textarea name="notes" label="Notes" rows="2" />
                <button class="btn btn-primary w-full">Create</button>
            </form>
        </x-modal>
    </x-page-header>

    @if ($care->isNotEmpty())
        <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($care as $section)
                <div class="card card-pad">
                    <div class="flex items-start justify-between gap-2">
                        <div><p class="font-bold">{{ $section['label'] }}</p><p class="text-xs text-muted">{{ $section['hint'] }}</p></div>
                        <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-sm font-extrabold text-brand">{{ $section['count'] }}</span>
                    </div>
                    <ul class="mt-3 space-y-1.5">
                        @foreach ($section['items'] as $item)
                            <li><a href="{{ $item['url'] }}" class="flex justify-between gap-2 text-sm hover:text-brand"><span class="truncate font-semibold">{{ $item['title'] }}</span><span @class(['shrink-0 text-xs', 'text-amber-600' => $item['urgent'], 'text-muted' => ! $item['urgent']])>{{ $item['meta'] }}</span></a></li>
                        @endforeach
                    </ul>
                    <a href="{{ $section['url'] }}" class="mt-3 inline-block text-xs font-bold text-brand">View all →</a>
                </div>
            @endforeach
        </div>
    @endif

    <x-filter-bar>
        <x-form.select name="status" label="Status" :options="$statuses" :value="request('status', 'pending')" />
        <x-form.select name="category" label="Category" :options="$categories" :value="request('category')" placeholder="All" />
        <label class="flex items-center gap-2 pb-2.5 text-sm font-semibold"><input type="checkbox" name="mine" value="1" class="checkbox" @checked(request('mine'))> Assigned to me</label>
        <label class="flex items-center gap-2 pb-2.5 text-sm font-semibold"><input type="checkbox" name="overdue" value="1" class="checkbox" @checked(request('overdue'))> Past due</label>
    </x-filter-bar>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Follow-up</th><th>Person</th><th>Category</th><th>Assigned to</th><th>Due</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($tasks as $task)
                    <tr>
                        <td class="max-w-80">
                            <p class="font-bold">{{ $task->title }}</p>
                            @if ($task->notes)<p class="text-xs text-muted">{{ Str::limit($task->notes, 90) }}</p>@endif
                            @if ($task->subject instanceof \App\Models\InvolvementRequest)
                                <a href="{{ route('admin.involvement.show', $task->subject) }}" class="text-xs font-bold text-brand">Open request →</a>
                            @endif
                        </td>
                        <td>@if ($task->profile)<a href="{{ route('admin.members.show', $task->profile) }}" class="font-semibold hover:text-brand">{{ $task->profile->full_name }}</a>@else — @endif</td>
                        <td><x-badge :value="$task->category" /></td>
                        <td class="text-sm">{{ $task->assignee?->name ?? '—' }}</td>
                        <td @class(['whitespace-nowrap text-sm', 'font-bold text-amber-600' => $task->isOverdue()])>{{ $task->due_date?->translatedFormat('j M Y') ?? '—' }}</td>
                        <td><x-badge :value="$task->status" /></td>
                        <td class="text-right whitespace-nowrap">
                            @if (in_array($task->status, [\App\Enums\FollowUpTaskStatus::Open, \App\Enums\FollowUpTaskStatus::InProgress], true))
                                <form method="POST" action="{{ route('admin.follow-ups.update', $task) }}" class="inline">@csrf @method('PATCH')<input type="hidden" name="status" value="done"><button class="btn btn-success btn-sm"><x-icon name="check" class="size-4" /> Done</button></form>
                                @if ($task->status === \App\Enums\FollowUpTaskStatus::Open)
                                    <form method="POST" action="{{ route('admin.follow-ups.update', $task) }}" class="inline">@csrf @method('PATCH')<input type="hidden" name="status" value="in_progress"><button class="btn btn-outline btn-sm">Start</button></form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty title="No follow-ups here" icon="clipboard">Everyone in this list has been cared for 🙌</x-empty></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $tasks->links() }}</div>
</x-layouts.admin>
