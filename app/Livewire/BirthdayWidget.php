<?php

namespace App\Livewire;

use App\Services\BirthdayService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * BIRTHDAYS widget: Today / Next 7 days / This month, with WhatsApp greeting links.
 */
class BirthdayWidget extends Component
{
    public string $range = 'week';

    public int $limit = 8;

    public function setRange(string $range): void
    {
        $this->range = array_key_exists($range, BirthdayService::RANGES) ? $range : 'week';
    }

    public function render(BirthdayService $birthdays): View
    {
        abort_unless(auth()->user()?->can('birthdays.view'), 403);

        $all = $birthdays->upcoming(auth()->user(), $this->range);

        return view('livewire.birthday-widget', [
            'rows' => $all->take($this->limit),
            'total' => $all->count(),
            'ranges' => BirthdayService::RANGES,
        ]);
    }
}
