<x-layouts.admin title="Store categories">
    <x-page-header title="Store categories" description="Group products in the store, e.g. Discipleship Books, Apparel, Accessories." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card overflow-x-auto lg:col-span-2">
            <table class="table">
                <thead><tr><th>Category</th><th class="text-right">Products</th><th>Visible</th><th></th></tr></thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr x-data="{ editing: false }">
                            <td>
                                <div x-show="! editing">
                                    <p class="font-bold">{{ $category->name }}</p>
                                    @if ($category->description)<p class="text-xs text-muted">{{ $category->description }}</p>@endif
                                </div>
                                <form x-show="editing" x-cloak method="POST" action="{{ route('admin.store.categories.update', $category) }}" class="grid gap-2 sm:grid-cols-6">
                                    @csrf @method('PUT')
                                    <input class="input py-1.5 sm:col-span-2" name="name" value="{{ $category->name }}" required>
                                    <input class="input py-1.5 sm:col-span-2" name="description" value="{{ $category->description }}" placeholder="Description">
                                    <input class="input py-1.5" type="number" name="sort_order" value="{{ $category->sort_order }}" min="0" title="Sort order">
                                    <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="checkbox" @checked($category->is_active)> Visible</label>
                                    <div class="flex gap-2 sm:col-span-6">
                                        <button class="btn btn-primary btn-sm">Save</button>
                                        <button type="button" class="btn btn-ghost btn-sm" x-on:click="editing = false">Cancel</button>
                                    </div>
                                </form>
                            </td>
                            <td class="text-right tabular-nums">{{ $category->products_count }}</td>
                            <td>@if ($category->is_active)<x-badge color="green">Visible</x-badge>@else<x-badge>Hidden</x-badge>@endif</td>
                            <td class="text-right whitespace-nowrap">
                                <button type="button" class="btn btn-ghost btn-sm" x-on:click="editing = ! editing"><x-icon name="pencil" class="size-4" /></button>
                                <form method="POST" action="{{ route('admin.store.categories.destroy', $category) }}" class="inline" data-confirm="Delete this category? Its products stay in the store without a category.">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="size-4" /></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty title="No categories yet" icon="tag" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.store.categories.store') }}" class="card card-pad h-fit space-y-4">
            @csrf
            <h2 class="font-extrabold uppercase">New category</h2>
            <x-form.input name="name" label="Name" required />
            <x-form.input name="description" label="Description" />
            <x-form.input name="sort_order" type="number" min="0" label="Sort order" value="0" />
            <x-form.checkbox name="is_active" label="Visible in the store" :checked="true" />
            <button class="btn btn-primary w-full">Add category</button>
        </form>
    </div>
</x-layouts.admin>
