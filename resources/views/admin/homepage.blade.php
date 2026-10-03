<x-layouts.admin title="Homepage">
    <x-page-header title="Homepage & Get Involved content" description="Texts and images shown on the public website. Empty fields fall back to the default text.">
        <a href="{{ route('home') }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="external" class="size-4" /> View website</a>
    </x-page-header>

    <form method="POST" action="{{ route('admin.homepage.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf @method('PUT')
        @foreach ($groups as $group => $definitions)
            <div class="card card-pad">
                <h2 class="font-extrabold uppercase">{{ str_replace('_', ' ', $group) }}</h2>
                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    @foreach ($definitions as $key => $definition)
                        @php $type = $definition['type'] ?? 'text'; $value = $stored[$key] ?? null; @endphp
                        @if ($type === 'image')
                            <div>
                                <x-form.image :name="$key" :label="$definition['label']" :current="$value ? Storage::disk('public')->url($value) : null" />
                                @if ($value)<label class="mt-2 inline-flex items-center gap-2 text-xs text-muted"><input type="checkbox" name="remove_{{ $key }}" value="1" class="checkbox"> Remove image</label>@endif
                            </div>
                        @elseif ($type === 'textarea')
                            <x-form.textarea :name="$key" :label="$definition['label']" :value="$value" :placeholder="$definition['default']" rows="4" class="md:col-span-2" />
                        @else
                            <x-form.input :name="$key" :label="$definition['label']" :value="$value" :placeholder="$definition['default']" />
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
        <button class="btn btn-primary">Save content</button>
    </form>
</x-layouts.admin>
