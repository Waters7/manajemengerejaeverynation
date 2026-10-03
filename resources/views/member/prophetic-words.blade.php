<x-layouts.member title="Prophetic Words">
    <x-page-header title="Prophetic words" description="Rekaman nubuatan pribadi untukmu. Hanya kamu dan tim pastoral yang dapat mendengarnya. Uji setiap nubuatan dengan Firman dan bicarakan dengan pemimpinmu (1 Tesalonika 5:20–21)." />

    <div class="space-y-4">
        @forelse ($words as $word)
            <article class="card card-pad">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold">{{ $word->title }}</h2>
                        <p class="text-sm text-muted">{{ $word->given_on?->translatedFormat('j F Y') ?? $word->created_at->translatedFormat('j F Y') }}{{ $word->given_by ? ' · '.$word->given_by : '' }}</p>
                    </div>
                    <a href="{{ route('prophetic-words.download', $word) }}" class="btn btn-outline btn-sm"><x-icon name="download" class="size-4" /> Download</a>
                </div>
                <audio controls preload="none" class="mt-4 w-full" src="{{ route('prophetic-words.audio', $word) }}"></audio>
                @if ($word->notes)
                    <p class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm whitespace-pre-line text-slate-700">{{ $word->notes }}</p>
                @endif
            </article>
        @empty
            <div class="card"><x-empty title="Belum ada rekaman" icon="music">Rekaman nubuatan akan muncul di sini setelah diunggah oleh tim gereja.</x-empty></div>
        @endforelse
    </div>
</x-layouts.member>
