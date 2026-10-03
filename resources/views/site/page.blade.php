<x-layouts.site :title="$page->title" :description="$page->meta_description" :transparent="true">
    @include('site.partials.page-hero', ['title' => $page->title, 'lead' => $page->excerpt, 'image' => $page->coverUrl()])
    <section class="py-16 sm:py-20">
        <article class="prose-church mx-auto max-w-3xl px-4 sm:px-6">{!! $page->body !!}</article>
    </section>
</x-layouts.site>
