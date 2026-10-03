<x-layouts.admin :title="$page->exists ? 'Edit page' : 'New page'">
    <x-page-header :title="$page->exists ? 'Edit '.$page->title : 'New page'" :back="route('admin.pages.index')" />
    <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($page->exists) @method('PUT') @endif
        <div class="card card-pad space-y-5 lg:col-span-2">
            <x-form.input name="title" label="Title" :value="$page->title" required />
            <x-form.input name="slug" label="Slug" :value="$page->slug" hint="Leave empty to generate from the title. Use “about” or “discipleship” to override those pages." />
            <x-form.input name="excerpt" label="Excerpt" :value="$page->excerpt" />
            <x-form.textarea name="body" label="Content" :value="$page->body" rows="16" hint="Blank lines become paragraphs. Basic HTML (h2, h3, p, strong, em, a, ul, ol, li, blockquote, img) is allowed." />
        </div>
        <div class="space-y-6">
            <div class="card card-pad space-y-4">
                @include('admin.partials.publish-fields', ['model' => $page])
                <x-form.textarea name="meta_description" label="SEO description" :value="$page->meta_description" rows="2" />
            </div>
            <div class="card card-pad"><x-form.image name="cover" label="Cover" :current="$page->coverUrl()" /></div>
            <button class="btn btn-primary w-full">Save page</button>
        </div>
    </form>
</x-layouts.admin>
