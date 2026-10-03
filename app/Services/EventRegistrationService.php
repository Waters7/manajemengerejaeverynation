<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Event registration with capacity, waiting list, QR ticket and check-in.
 */
class EventRegistrationService
{
    public function __construct(
        private PeopleService $people,
        private TimelineRecorder $timeline,
    ) {}

    /**
     * @param  array{name: string, whatsapp: string, email?: ?string}  $data
     */
    public function register(Event $event, array $data, ?User $user = null): EventRegistration
    {
        if (! $event->registrationOpen()) {
            throw ValidationException::withMessages(['name' => 'Registration for this event is closed.']);
        }

        return DB::transaction(function () use ($event, $data, $user) {
            // Lock the event row so concurrent registrations cannot exceed capacity.
            $event = Event::whereKey($event->id)->lockForUpdate()->first();
            $whatsapp = WhatsApp::normalize($data['whatsapp']);

            $existing = $event->registrations()->where('whatsapp', $whatsapp)->where('status', '!=', RegistrationStatus::Cancelled->value)->first();
            if ($existing) {
                return $existing;
            }

            $status = RegistrationStatus::Registered;
            if ($event->isFull()) {
                if (! $event->waiting_list_enabled) {
                    throw ValidationException::withMessages(['name' => 'This event is fully booked.']);
                }
                $status = RegistrationStatus::WaitingList;
            }

            $profile = $user?->profile ?? $this->people->findOrCreate([
                'full_name' => $data['name'],
                'whatsapp' => $whatsapp,
                'email' => $data['email'] ?? null,
                'source' => 'event',
            ]);

            $registration = $event->registrations()->create([
                'profile_id' => $profile->id,
                'user_id' => $user?->id,
                'name' => $data['name'],
                'whatsapp' => $whatsapp,
                'email' => $data['email'] ?? null,
                'status' => $status,
                'code' => Str::upper(Str::random(10)),
            ]);

            $this->timeline->record($profile, 'event', "Registered for {$event->title}", $status === RegistrationStatus::WaitingList ? 'Waiting list' : null, $event);

            return $registration;
        });
    }

    public function cancel(EventRegistration $registration): void
    {
        DB::transaction(function () use ($registration) {
            $wasConfirmed = $registration->status === RegistrationStatus::Registered;
            $registration->update(['status' => RegistrationStatus::Cancelled]);

            if ($wasConfirmed) {
                $this->promoteWaitingList($registration->event);
            }
        });
    }

    public function promoteWaitingList(Event $event): ?EventRegistration
    {
        if ($event->isFull()) {
            return null;
        }
        $next = $event->registrations()->where('status', RegistrationStatus::WaitingList->value)->oldest()->first();
        $next?->update(['status' => RegistrationStatus::Registered]);

        return $next;
    }

    public function checkIn(EventRegistration $registration): EventRegistration
    {
        if ($registration->status === RegistrationStatus::Cancelled) {
            throw ValidationException::withMessages(['code' => 'This registration was cancelled.']);
        }
        if (! $registration->checked_in_at) {
            $registration->update([
                'checked_in_at' => now(),
                'checked_in_by' => Auth::id(),
                'status' => RegistrationStatus::Registered,
            ]);
        }

        return $registration;
    }

    public function undoCheckIn(EventRegistration $registration): void
    {
        $registration->update(['checked_in_at' => null, 'checked_in_by' => null]);
    }
}
