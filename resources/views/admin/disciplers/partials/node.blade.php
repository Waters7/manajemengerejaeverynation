{{-- One node of the discipleship tree; renders its children recursively. --}}
<li>
    <a href="{{ route('admin.members.show', $node['profile']) }}" class="inline-flex items-center gap-2 rounded-2xl border border-line bg-white py-1.5 pr-3 pl-1.5 shadow-xs hover:border-brand/50">
        <x-avatar :profile="$node['profile']" size="size-7" />
        <span class="text-sm font-bold">{{ $node['profile']->full_name }}</span>
        @if ($node['profile']->currentStage)
            <span class="rounded-full px-2 py-0.5 text-[0.6rem] font-bold text-white uppercase" style="background: {{ $node['profile']->currentStage->color }}">{{ $node['profile']->currentStage->name }}</span>
        @endif
        @if (count($node['children']))
            <span class="text-xs text-muted">· {{ count($node['children']) }}</span>
        @endif
    </a>
    @if (count($node['children']))
        <ul class="mt-2 ml-4 space-y-2 border-l-2 border-brand-100 pl-5">
            @foreach ($node['children'] as $child)
                @include('admin.disciplers.partials.node', ['node' => $child])
            @endforeach
        </ul>
    @endif
</li>
