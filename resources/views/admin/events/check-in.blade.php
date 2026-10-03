<x-layouts.admin :title="'Check-in — '.$event->title">
    <x-page-header :title="'Check-in: '.$event->title" :eyebrow="$event->starts_at->translatedFormat('l, j F Y · H:i')" :back="route('admin.events.show', $event)" />
    @livewire(\App\Livewire\EventCheckIn::class, ['event' => $event])
</x-layouts.admin>
