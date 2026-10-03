<x-layouts.admin :title="$sermon->exists ? 'Edit sermon' : 'New sermon'">
    <x-page-header :title="$sermon->exists ? 'Edit sermon' : 'New sermon'" :back="route('admin.sermons.index')" />
    @php
        $points = old('points', $sermon->exists ? $sermon->points->map(fn ($p) => ['title' => $p->title, 'body' => $p->body])->all() : []);
        $points = $points ?: [['title' => '', 'body' => '']];
    @endphp
    <form method="POST" action="{{ $sermon->exists ? route('admin.sermons.update', $sermon) : route('admin.sermons.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($sermon->exists) @method('PUT') @endif
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-pad space-y-5">
                <x-form.input name="title" label="Title" :value="$sermon->title" required />
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-form.input name="speaker" label="Speaker" :value="$sermon->speaker" />
                    <x-form.input name="preached_on" type="date" label="Date" :value="$sermon->preached_on" required />
                    <x-form.input name="bible_text" label="Bible text" :value="$sermon->bible_text" />
                </div>
                <x-form.textarea name="summary" label="Summary" :value="$sermon->summary" rows="4" />
                <x-form.textarea name="reflection" label="Reflection" :value="$sermon->reflection" rows="2" />
                <x-form.textarea name="application" label="Application" :value="$sermon->application" rows="2" />
            </div>
            <div class="card card-pad" x-data="{ points: @js(array_values($points)) }">
                <div class="flex items-center justify-between">
                    <h2 class="font-extrabold uppercase">Key points</h2>
                    <button type="button" x-on:click="points.push({ title: '', body: '' })" class="btn btn-outline btn-sm"><x-icon name="plus" class="size-4" /> Point</button>
                </div>
                <template x-for="(point, i) in points" :key="i">
                    <div class="mt-4 grid gap-2 rounded-2xl border border-line p-4">
                        <div class="flex gap-2">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-brand text-sm font-extrabold text-white" x-text="i + 1"></span>
                            <input class="input" :name="'points[' + i + '][title]'" x-model="point.title" placeholder="Point title">
                            <button type="button" x-on:click="points.splice(i, 1)" class="btn btn-ghost btn-sm"><x-icon name="x" class="size-4" /></button>
                        </div>
                        <textarea class="input" rows="2" :name="'points[' + i + '][body]'" x-model="point.body" placeholder="Explanation (optional)"></textarea>
                    </div>
                </template>
            </div>
        </div>
        <div class="space-y-6">
            <div class="card card-pad space-y-4">
                @include('admin.partials.publish-fields', ['model' => $sermon])
                <x-form.select name="sermon_series_id" label="Series" :options="$series" :value="$sermon->sermon_series_id" placeholder="—" />
                <x-form.input name="youtube_url" type="url" label="YouTube URL" :value="$sermon->youtube_url" />
                <x-form.input name="spotify_url" type="url" label="Spotify URL" :value="$sermon->spotify_url" />
            </div>
            <div class="card card-pad"><x-form.image name="cover" label="Cover (defaults to YouTube thumbnail)" :current="$sermon->cover_path ? $sermon->coverUrl() : null" /></div>
            <button class="btn btn-primary w-full">Save sermon</button>
        </div>
    </form>
</x-layouts.admin>
