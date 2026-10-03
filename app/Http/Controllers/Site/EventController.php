<?php

namespace App\Http\Controllers\Site;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\EventRegistrationRequest;
use App\Models\Event;
use App\Models\EventCategory;
use App\Services\EventRegistrationService;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $past = $request->boolean('past');

        $events = Event::published()
            ->with('category')
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->string('category'))))
            ->when($past,
                fn ($q) => $q->where('starts_at', '<', now())->orderByDesc('starts_at'),
                fn ($q) => $q->upcoming())
            ->paginate(9)
            ->withQueryString();

        return view('site.events.index', [
            'events' => $events,
            'categories' => EventCategory::orderBy('sort_order')->get(),
            'past' => $past,
        ]);
    }

    public function show(Event $event): View
    {
        abort_unless($event->isLive(), 404);

        return view('site.events.show', [
            'event' => $event->load('category'),
            'seatsLeft' => $event->capacity ? max(0, $event->capacity - $event->seatsTaken()) : null,
        ]);
    }

    public function register(EventRegistrationRequest $request, Event $event, EventRegistrationService $service): RedirectResponse
    {
        abort_unless($event->isLive(), 404);

        $registration = $service->register($event, $request->safe()->only(['name', 'whatsapp', 'email']), $request->user());

        return redirect()->route('events.ticket', [$event->slug, $registration->code])
            ->with('status', $registration->status === RegistrationStatus::WaitingList
                ? 'Kamu masuk waiting list. Kami akan mengabari jika ada tempat.'
                : 'Pendaftaran berhasil! Simpan QR code ini untuk check-in.');
    }

    public function ticket(Event $event, string $code, QrCodeService $qr): View
    {
        $registration = $event->registrations()->where('code', $code)->firstOrFail();

        return view('site.events.ticket', [
            'event' => $event,
            'registration' => $registration,
            'qr' => $qr->svg($registration->code),
        ]);
    }
}
