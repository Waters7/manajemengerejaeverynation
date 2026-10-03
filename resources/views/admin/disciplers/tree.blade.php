<x-layouts.admin title="Discipleship Tree">
    <x-page-header title="Discipleship tree" description="A spiritual family tree that shows multiplication — disciples making disciples. It is not a ranking." :back="route('admin.disciplers.index')" />

    <form method="GET" class="card mb-6 flex flex-wrap items-end gap-3 p-4">
        <x-form.select name="root" label="Start the tree from" :options="$people" :value="request('root')" placeholder="All families" class="min-w-64" />
        <button class="btn btn-dark btn-sm">Show</button>
    </form>

    <div class="grid gap-6 lg:grid-cols-2">
        @forelse ($trees as $tree)
            <div class="card card-pad overflow-x-auto">
                <ul class="space-y-2">
                    @include('admin.disciplers.partials.node', ['node' => $tree])
                </ul>
            </div>
        @empty
            <div class="card lg:col-span-2"><x-empty title="No discipleship relationships yet" icon="tree" /></div>
        @endforelse
    </div>
</x-layouts.admin>
