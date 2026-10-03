<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InterestAction;
use App\Http\Controllers\Controller;
use App\Models\InvolvementInterest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "Saya tertarik untuk…" options on Get Involved and the Connect Card — fully configurable.
 */
class InterestController extends Controller
{
    public function index(): View
    {
        return view('admin.interests', [
            'interests' => InvolvementInterest::withCount('requests')->orderBy('sort_order')->get(),
            'actions' => InterestAction::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        InvolvementInterest::create($this->validated($request) + ['sort_order' => (InvolvementInterest::max('sort_order') ?? 0) + 1]);

        return back()->with('status', 'Interest added.');
    }

    public function update(Request $request, InvolvementInterest $interest): RedirectResponse
    {
        $interest->update($this->validated($request));

        return back()->with('status', 'Interest updated.');
    }

    public function destroy(InvolvementInterest $interest): RedirectResponse
    {
        if ($interest->requests()->exists()) {
            $interest->update(['is_active' => false]);

            return back()->with('status', 'This interest has been used before, so it was deactivated instead of deleted.');
        }
        $interest->delete();

        return back()->with('status', 'Interest deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:190'],
            'action' => ['nullable', Rule::enum(InterestAction::class)],
            'on_connect_card' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:999'],
        ]);
    }
}
