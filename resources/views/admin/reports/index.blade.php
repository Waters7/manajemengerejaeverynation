<x-layouts.admin title="Reports">
    <x-page-header title="Reports" description="See how the community is growing — every report can be filtered and exported to Excel or CSV." />
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($catalog as $key => $report)
            <a href="{{ route('admin.reports.show', $key) }}" class="card card-hover card-pad">
                <x-icon name="chart" class="size-6 text-brand" />
                <h2 class="mt-3 text-lg font-extrabold">{{ $report['title'] }}</h2>
                <p class="mt-1 text-sm text-muted">{{ $report['description'] }}</p>
            </a>
        @endforeach
    </div>
</x-layouts.admin>
