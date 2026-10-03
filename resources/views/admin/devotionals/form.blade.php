<x-layouts.admin :title="$devotional->exists ? 'Edit devotional' : 'New devotional'">
    <x-page-header :title="$devotional->exists ? 'Edit devotional' : 'New devotional'" :back="route('admin.devotionals.index')" />
    <form method="POST" action="{{ $devotional->exists ? route('admin.devotionals.update', $devotional) : route('admin.devotionals.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($devotional->exists) @method('PUT') @endif
        <div class="card card-pad space-y-5 lg:col-span-2">
            <x-form.input name="title" label="Title" :value="$devotional->title" required />
            <div class="grid gap-5 sm:grid-cols-3">
                <x-form.textarea name="verse" label="Verse" :value="$devotional->verse" rows="2" class="sm:col-span-2" />
                <x-form.input name="bible_reference" label="Bible reference" :value="$devotional->bible_reference" placeholder="Galatia 2:20" />
            </div>
            <x-form.textarea name="opening" label="Opening" :value="$devotional->opening" rows="2" />
            <x-form.textarea name="body" label="Devotional" :value="$devotional->body" rows="10" required hint="Blank lines become paragraphs. Basic HTML is allowed." />
            <x-form.textarea name="reflection" label="Reflection" :value="$devotional->reflection" rows="2" />
            <x-form.textarea name="application" label="Application" :value="$devotional->application" rows="2" />
            <x-form.textarea name="prayer" label="Prayer" :value="$devotional->prayer" rows="2" />
        </div>
        <div class="space-y-6">
            <div class="card card-pad space-y-4">
                @include('admin.partials.publish-fields', ['model' => $devotional])
                <x-form.input name="devotional_date" type="date" label="Date" :value="$devotional->devotional_date" required />
                <x-form.input name="author" label="Author" :value="$devotional->author" />
            </div>
            <div class="card card-pad"><x-form.image name="cover" label="Cover" :current="$devotional->coverUrl()" /></div>
            <button class="btn btn-primary w-full">Save devotional</button>
        </div>
    </form>
</x-layouts.admin>
