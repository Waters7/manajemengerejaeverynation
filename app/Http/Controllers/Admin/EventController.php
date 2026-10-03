<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Campus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventRegistration;
use App\Models\LifeGroup;
use App\Services\AccessScope;
use App\Services\AuditLogger;
use App\Services\EventRegistrationService;
use App\Services\Exporter;
use App\Services\HtmlSanitizer;
use App\Services\MediaService;
use App\Services\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function index(Request $request): View
    {
        $past = $request->boolean('past');

        $events = $this->scope->events(Event::query(), $request->user())
            ->with(['category', 'campus'])
            ->withCount(['registrations as registered_count' => fn ($q) => $q->where('status', RegistrationStatus::Registered->value), 'registrations as waiting_count' => fn ($q) => $q->where('status', RegistrationStatus::WaitingList->value), 'registrations as checked_in_count' => fn ($q) => $q->whereNotNull('checked_in_at')])
            ->when($request->filled('category'), fn ($q) => $q->where('event_category_id', $request->integer('category')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('campus'), fn ($q) => $q->where('campus_id', $request->integer('campus')))
            ->when($past, fn ($q) => $q->where('starts_at', '<', now())->orderByDesc('starts_at'), fn ($q) => $q->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at'))
            ->paginate(20)
            ->withQueryString();

        return view('admin.events.index', [
            'events' => $events,
            'categories' => EventCategory::orderBy('sort_order')->pluck('name', 'id'),
            'statuses' => ContentStatus::options(),
            'past' => $past,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('events.manage'), 403);

        return view('admin.events.form', $this->formData($request, new Event([
            'status' => ContentStatus::Draft,
            'starts_at' => today()->next('Sunday')->setTime(10, 0),
            'waiting_list_enabled' => true,
            'campus_id' => $request->integer('campus') ?: null,
        ])));
    }

    public function store(EventRequest $request, MediaService $media): RedirectResponse
    {
        $data = $this->prepare($request, $media);
        $this->guardCampus($request, $data);

        $event = Event::create($data + ['created_by' => $request->user()->id]);
        $this->logPublish($event);

        return redirect()->route('admin.events.show', $event)->with('status', 'Event created.');
    }

    public function show(Request $request, Event $event, WhatsApp $whatsApp): View
    {
        $this->authorize('update', $event);

        return view('admin.events.show', [
            'event' => $event->load(['category', 'campus', 'lifeGroup']),
            'registrations' => $event->registrations()->with('profile')
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderByRaw("case status when 'registered' then 0 when 'waiting_list' then 1 else 2 end")
                ->oldest()->paginate(50)->withQueryString(),
            'counts' => $event->registrations()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'checkedIn' => $event->registrations()->whereNotNull('checked_in_at')->count(),
            'statuses' => RegistrationStatus::options(),
            'whatsApp' => $whatsApp,
        ]);
    }

    public function edit(Request $request, Event $event): View
    {
        $this->authorize('update', $event);

        return view('admin.events.form', $this->formData($request, $event));
    }

    public function update(EventRequest $request, Event $event, MediaService $media, EventRegistrationService $registrations): RedirectResponse
    {
        $data = $this->prepare($request, $media, $event);
        $this->guardCampus($request, $data);
        $wasLive = $event->isLive();

        $event->update($data);
        if (! $wasLive) {
            $this->logPublish($event);
        }

        // Capacity increased → move people up from the waiting list.
        while ($event->fresh()->capacity === null || ! $event->fresh()->isFull()) {
            if (! $registrations->promoteWaitingList($event->fresh())) {
                break;
            }
        }

        return redirect()->route('admin.events.show', $event)->with('status', 'Event updated.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);
        $event->delete();

        return redirect()->route('admin.events.index')->with('status', 'Event deleted.');
    }

    public function checkIn(Event $event): View
    {
        $this->authorize('update', $event);

        return view('admin.events.check-in', ['event' => $event]);
    }

    /** Manual check-in by code (QR scanners type the code followed by Enter). */
    public function processCheckIn(Request $request, Event $event, EventRegistrationService $service): RedirectResponse
    {
        $this->authorize('update', $event);
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);

        $registration = $event->registrations()->where('code', strtoupper(trim($data['code'])))->first();
        if (! $registration) {
            return back()->with('error', 'Code not found for this event.');
        }
        $service->checkIn($registration);

        return back()->with('status', "{$registration->name} checked in ✓");
    }

    public function updateRegistration(Request $request, EventRegistration $registration, EventRegistrationService $service): RedirectResponse
    {
        $this->authorize('update', $registration->event);
        $data = $request->validate(['action' => ['required', Rule::in(['check_in', 'undo_check_in', 'cancel', 'confirm'])]]);

        match ($data['action']) {
            'check_in' => $service->checkIn($registration),
            'undo_check_in' => $service->undoCheckIn($registration),
            'cancel' => $service->cancel($registration),
            'confirm' => $registration->update(['status' => RegistrationStatus::Registered]),
        };

        return back()->with('status', 'Registration updated.');
    }

    public function export(Request $request, Event $event, Exporter $exporter): StreamedResponse
    {
        $this->authorize('update', $event);

        $rows = $event->registrations()->oldest()->get()->map(fn (EventRegistration $r) => [
            $r->created_at->format('Y-m-d H:i'), $r->name, $r->whatsapp, $r->email, $r->status->label(), $r->code, $r->checked_in_at?->format('Y-m-d H:i'),
        ]);

        return $exporter->download('registrations-'.$event->slug, $request->input('format', 'xlsx'),
            ['Registered', 'Name', 'WhatsApp', 'Email', 'Status', 'Code', 'Checked in'], $rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepare(EventRequest $request, MediaService $media, ?Event $event = null): array
    {
        $data = $request->safe()->except('cover');
        $data['description'] = HtmlSanitizer::clean($data['description'] ?? null);
        $data['contact_whatsapp'] = WhatsApp::normalize($data['contact_whatsapp'] ?? null);
        $data['cover_path'] = $media->replace($event?->cover_path, $request->file('cover'), 'events');

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function guardCampus(Request $request, array $data): void
    {
        if (! $this->scope->isChurchWide($request->user())) {
            abort_unless(in_array((int) ($data['campus_id'] ?? 0), $this->scope->campusIds($request->user()), true), 403, 'Choose one of your campuses.');
        }
    }

    private function logPublish(Event $event): void
    {
        if ($event->isLive()) {
            app(AuditLogger::class)->log(AuditAction::Publish, $event, "Published event “{$event->title}”");
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, Event $event): array
    {
        $churchWide = $this->scope->isChurchWide($request->user());

        return [
            'event' => $event,
            'categories' => EventCategory::orderBy('sort_order')->pluck('name', 'id'),
            'statuses' => ContentStatus::options(),
            'campuses' => Campus::when(! $churchWide, fn ($q) => $q->whereIn('id', $this->scope->campusIds($request->user())))->orderBy('name')->pluck('name', 'id'),
            'lifeGroups' => $this->scope->lifeGroups(LifeGroup::active(), $request->user())->orderBy('name')->pluck('name', 'id'),
            'churchWide' => $churchWide,
        ];
    }
}
