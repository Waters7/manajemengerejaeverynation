<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\CarePriority;
use App\Enums\CareStatus;
use App\Http\Controllers\Controller;
use App\Models\PastoralCareCategory;
use App\Models\PastoralCareRequest;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CARE → Pastoral Care (restricted). Descriptions and notes are encrypted at rest
 * and never copied into the audit log.
 */
class PastoralCareController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PastoralCareRequest::class);

        return view('admin.pastoral-care.index', [
            'cases' => PastoralCareRequest::with(['category', 'profile', 'assignee'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->whereIn('status', [CareStatus::Open->value, CareStatus::InProgress->value]))
                ->when($request->filled('category'), fn ($q) => $q->where('pastoral_care_category_id', $request->integer('category')))
                ->when($request->boolean('mine'), fn ($q) => $q->where('assigned_to', $request->user()->id))
                ->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 else 2 end")
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'statuses' => CareStatus::options(),
            'categories' => PastoralCareCategory::active()->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', PastoralCareRequest::class);

        return view('admin.pastoral-care.form', $this->formData(new PastoralCareRequest([
            'status' => CareStatus::Open,
            'priority' => CarePriority::Normal,
            'profile_id' => $request->integer('profile') ?: null,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PastoralCareRequest::class);
        $care = PastoralCareRequest::create($this->validated($request) + ['opened_by' => $request->user()->id]);

        return redirect()->route('admin.pastoral-care.show', $care)->with('status', 'Care case opened.');
    }

    public function show(PastoralCareRequest $care): View
    {
        $this->authorize('view', $care);

        return view('admin.pastoral-care.show', [
            'care' => $care->load(['category', 'profile', 'assignee', 'opener', 'notes.author']),
        ]);
    }

    public function edit(PastoralCareRequest $care): View
    {
        $this->authorize('update', $care);

        return view('admin.pastoral-care.form', $this->formData($care));
    }

    public function update(Request $request, PastoralCareRequest $care): RedirectResponse
    {
        $this->authorize('update', $care);
        $data = $this->validated($request);
        if (in_array($data['status'], [CareStatus::Resolved->value, CareStatus::Closed->value], true) && ! $care->resolved_at) {
            $data['resolved_at'] = now();
        }
        $care->update($data);

        return redirect()->route('admin.pastoral-care.show', $care)->with('status', 'Care case updated.');
    }

    public function note(Request $request, PastoralCareRequest $care): RedirectResponse
    {
        $this->authorize('update', $care);
        $care->notes()->create($request->validate(['body' => ['required', 'string', 'max:5000']]) + ['user_id' => $request->user()->id]);

        if ($care->status === CareStatus::Open) {
            $care->update(['status' => CareStatus::InProgress]);
        }

        return back()->with('status', 'Pastoral note added.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'profile_id' => ['nullable', Rule::exists('profiles', 'id')],
            'pastoral_care_category_id' => ['nullable', Rule::exists('pastoral_care_categories', 'id')],
            'requester_name' => ['nullable', 'string', 'max:120'],
            'contact' => ['nullable', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::enum(CareStatus::class)],
            'priority' => ['required', Rule::enum(CarePriority::class)],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(PastoralCareRequest $care): array
    {
        return [
            'care' => $care,
            'categories' => PastoralCareCategory::active()->pluck('name', 'id'),
            'statuses' => CareStatus::options(),
            'priorities' => CarePriority::options(),
            'people' => Profile::orderBy('full_name')->limit(2000)->pluck('full_name', 'id'),
            'team' => User::permission('pastoral.view')->where('account_status', AccountStatus::Active->value)->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
