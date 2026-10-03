<x-layouts.admin title="Interests">
    <x-page-header title="Get Involved interests" description="The “Saya tertarik untuk…” cards. The behaviour (e.g. show ministry picker, create prayer request) follows the selected action, so labels can be renamed freely." />

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Order</th><th>Label</th><th>Behaviour</th><th>Connect Card</th><th>Active</th><th>Used</th><th></th></tr></thead>
            <tbody>
                @foreach ($interests as $interest)
                    <tr x-data="{ edit: false }">
                        <td colspan="7" class="!p-0">
                            <div class="grid grid-cols-[4rem_1fr_12rem_7rem_5rem_4rem_auto] items-center gap-3 px-4 py-3" x-show="!edit">
                                <span class="text-muted">{{ $interest->sort_order }}</span>
                                <span class="font-bold">{{ $interest->name }}@if ($interest->description)<span class="block text-xs font-normal text-muted">{{ $interest->description }}</span>@endif</span>
                                <span class="text-sm">{{ $interest->action?->label() ?? '—' }}</span>
                                <span>{{ $interest->on_connect_card ? 'Yes' : '—' }}</span>
                                <span>@if ($interest->is_active)<x-badge color="green">Yes</x-badge>@else<x-badge color="gray">No</x-badge>@endif</span>
                                <span class="text-sm">{{ $interest->requests_count }}</span>
                                <span class="flex justify-end gap-1">
                                    <button type="button" x-on:click="edit = true" class="btn btn-outline btn-sm">Edit</button>
                                    <form method="POST" action="{{ route('admin.interests.destroy', $interest) }}" data-confirm="Delete this interest?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="size-4" /></button></form>
                                </span>
                            </div>
                            <form x-show="edit" x-cloak method="POST" action="{{ route('admin.interests.update', $interest) }}" class="grid gap-3 bg-slate-50 p-4 sm:grid-cols-6 sm:items-end">
                                @csrf @method('PUT')
                                <x-form.input name="sort_order" type="number" label="Order" :value="$interest->sort_order" />
                                <x-form.input name="name" label="Label" :value="$interest->name" required class="sm:col-span-2" />
                                <x-form.select name="action" label="Behaviour" :options="$actions" :value="$interest->action" placeholder="None" />
                                <x-form.input name="description" label="Description" :value="$interest->description" class="sm:col-span-2" />
                                <x-form.checkbox name="on_connect_card" label="On Connect Card" :checked="$interest->on_connect_card" />
                                <x-form.checkbox name="is_active" label="Active" :checked="$interest->is_active" />
                                <div class="flex gap-2"><button class="btn btn-dark btn-sm">Save</button><button type="button" x-on:click="edit = false" class="btn btn-ghost btn-sm">Cancel</button></div>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.interests.store') }}" class="card card-pad mt-6 grid gap-3 sm:grid-cols-6 sm:items-end">
        @csrf
        <h2 class="font-extrabold uppercase sm:col-span-6">Add interest</h2>
        <x-form.input name="name" label="Label" required class="sm:col-span-2" />
        <x-form.select name="action" label="Behaviour" :options="$actions" placeholder="None" />
        <x-form.input name="description" label="Description" class="sm:col-span-2" />
        <div class="space-y-1">
            <x-form.checkbox name="on_connect_card" label="On Connect Card" />
            <x-form.checkbox name="is_active" label="Active" :checked="true" />
        </div>
        <button class="btn btn-primary btn-sm">Add</button>
    </form>
</x-layouts.admin>
