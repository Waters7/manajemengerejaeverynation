<x-layouts.admin :title="$definition['title']">
    <x-page-header :title="$definition['title']" :description="$definition['description']" :back="route('admin.reports.index')">
        @can('reports.export')
            <a href="{{ route('admin.reports.export', [$key] + request()->query()) }}" class="btn btn-outline btn-sm"><x-icon name="download" class="size-4" /> Excel</a>
            <a href="{{ route('admin.reports.export', [$key] + request()->query() + ['format' => 'csv']) }}" class="btn btn-outline btn-sm">CSV</a>
        @endcan
    </x-page-header>

    <x-filter-bar>
        @if (in_array('date', $definition['filters'], true))
            <x-form.input name="from" type="date" label="From" :value="request('from', now()->subMonths(11)->startOfMonth()->toDateString())" />
            <x-form.input name="to" type="date" label="To" :value="request('to', today()->toDateString())" />
        @endif
        @foreach (['campus' => 'Campus', 'lifegroup' => 'LifeGroup', 'leader' => 'Leader', 'program' => 'Program', 'stage' => 'Stage', 'ministry' => 'Ministry', 'status' => 'Status'] as $filter => $label)
            @if (in_array($filter, $definition['filters'], true) && count($options[$filter]))
                <x-form.select :name="$filter" :label="$label" :options="$options[$filter]" :value="request($filter)" placeholder="All" />
            @endif
        @endforeach
    </x-filter-bar>

    @php $barColumn = $result['bar']; @endphp
    <div class="card overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    @foreach ($result['columns'] as $i => $column)
                        <th @class(['text-right' => $i > 0 && $i !== $barColumn, 'w-1/3' => $i === $barColumn])>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($result['rows'] as $row)
                    <tr>
                        @foreach ($row as $i => $cell)
                            @if ($i === $barColumn && is_numeric($cell))
                                <td>
                                    <div class="flex items-center gap-3" title="{{ $result['columns'][$i] }}: {{ $cell }}">
                                        <div class="h-3 grow overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-brand" style="width: {{ max(2, round($cell / $max * 100)) }}%"></div>
                                        </div>
                                        <span class="w-12 shrink-0 text-right font-bold tabular-nums">{{ $cell }}</span>
                                    </div>
                                </td>
                            @else
                                <td @class(['text-right tabular-nums' => $i > 0 && is_numeric($cell), 'font-semibold' => $i === 0])>{{ $cell }}</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($result['columns']) }}"><x-empty title="No data for these filters" icon="chart" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
