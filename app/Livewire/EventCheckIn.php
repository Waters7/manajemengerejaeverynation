<?php

namespace App\Livewire;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Services\EventRegistrationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Door check-in: scan the QR ticket (scanner types the code + Enter) or search by name.
 */
class EventCheckIn extends Component
{
    #[Locked]
    public int $eventId;

    public string $code = '';

    public string $search = '';

    public ?string $message = null;

    public bool $success = false;

    public function mount(Event $event): void
    {
        $this->authorize('update', $event);
        $this->eventId = $event->id;
    }

    public function checkInCode(EventRegistrationService $service): void
    {
        $registration = $this->event()->registrations()->where('code', strtoupper(trim($this->code)))->first();
        $this->code = '';

        if (! $registration) {
            [$this->success, $this->message] = [false, 'Code not found for this event.'];

            return;
        }

        $this->checkIn($registration->id, $service);
    }

    public function checkIn(int $registrationId, EventRegistrationService $service): void
    {
        $registration = $this->event()->registrations()->findOrFail($registrationId);

        if ($registration->status === RegistrationStatus::Cancelled) {
            [$this->success, $this->message] = [false, "{$registration->name}'s registration was cancelled."];

            return;
        }

        $already = $registration->checked_in_at !== null;
        $service->checkIn($registration);
        [$this->success, $this->message] = [true, $already ? "{$registration->name} was already checked in." : "Welcome, {$registration->name}! ✓"];
    }

    private function event(): Event
    {
        $event = Event::findOrFail($this->eventId);
        $this->authorize('update', $event);

        return $event;
    }

    public function render(): View
    {
        $event = Event::findOrFail($this->eventId);

        return view('livewire.event-check-in', [
            'event' => $event,
            'total' => $event->registrations()->where('status', RegistrationStatus::Registered->value)->count(),
            'checkedIn' => $event->registrations()->whereNotNull('checked_in_at')->count(),
            'results' => strlen(trim($this->search)) >= 2
                ? $event->registrations()->where('status', '!=', RegistrationStatus::Cancelled->value)
                    ->where(fn ($q) => $q->where('name', 'like', '%'.trim($this->search).'%')->orWhere('whatsapp', 'like', '%'.ltrim(preg_replace('/\D/', '', $this->search) ?: '~', '0').'%'))
                    ->limit(10)->get()
                : collect(),
        ]);
    }
}
