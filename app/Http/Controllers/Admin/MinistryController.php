<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Availability;
use App\Enums\VolunteerStatus;
use App\Http\Controllers\Controller;
use App\Models\Ministry;
use App\Models\MinistryRole;
use App\Models\Profile;
use App\Models\User;
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MinistryController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function index(Request $request): View
    {
        $ministries = $this->scope->ministries(Ministry::query(), $request->user())
            ->with('coordinator')
            ->withCount(['activeMembers', 'members as orientation_count' => fn ($q) => $q->where('status', VolunteerStatus::Orientation->value), 'applications as waiting_count' => fn ($q) => $q->awaiting()])
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        return view('admin.ministries.index', ['ministries' => $ministries]);
    }

    public function create(): View
    {
        $this->authorize('create', Ministry::class);

        return view('admin.ministries.form', $this->formData(new Ministry(['is_active' => true, 'accepting_volunteers' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Ministry::class);
        $ministry = Ministry::create($this->validated($request));
        $ministry->roles()->create(['name' => 'Team Member']);

        return redirect()->route('admin.ministries.show', $ministry)->with('status', 'Ministry created.');
    }

    public function show(Request $request, Ministry $ministry): View
    {
        $this->authorize('view', $ministry);

        return view('admin.ministries.show', [
            'ministry' => $ministry->load(['coordinator', 'roles']),
            'members' => $ministry->members()->with(['profile', 'role'])->get()->sortBy([fn ($m) => $m->status === VolunteerStatus::Active ? 0 : 1, fn ($m) => $m->profile->full_name]),
            'applications' => $ministry->applications()->awaiting()->latest()->get(),
            'schedule' => $ministry->schedules()->with(['profile', 'role'])->whereDate('serve_date', '>=', today())->orderBy('serve_date')->limit(20)->get(),
            'statuses' => VolunteerStatus::options(),
            'availability' => Availability::options(),
            'canManage' => $request->user()->can('update', $ministry),
            'candidates' => $this->scope->profiles(Profile::query(), $request->user())
                ->whereNotIn('id', $ministry->members()->select('profile_id'))->orderBy('full_name')->limit(800)->pluck('full_name', 'id'),
        ]);
    }

    public function edit(Ministry $ministry): View
    {
        $this->authorize('update', $ministry);

        return view('admin.ministries.form', $this->formData($ministry));
    }

    public function update(Request $request, Ministry $ministry): RedirectResponse
    {
        $this->authorize('update', $ministry);
        $data = $this->validated($request, $ministry);
        if (! $this->scope->isChurchWide($request->user())) {
            unset($data['coordinator_id']);
        }
        $ministry->update($data);

        return redirect()->route('admin.ministries.show', $ministry)->with('status', 'Ministry updated.');
    }

    public function destroy(Ministry $ministry): RedirectResponse
    {
        $this->authorize('delete', $ministry);
        abort_if($ministry->activeMembers()->exists(), 422, 'This ministry still has active volunteers. Set it to inactive instead.');
        $ministry->delete();

        return redirect()->route('admin.ministries.index')->with('status', 'Ministry deleted.');
    }

    public function storeRole(Request $request, Ministry $ministry): RedirectResponse
    {
        $this->authorize('update', $ministry);
        $ministry->roles()->create($request->validate(['name' => ['required', 'string', 'max:80'], 'description' => ['nullable', 'string', 'max:500']]));

        return back()->with('status', 'Role added.');
    }

    public function destroyRole(MinistryRole $role): RedirectResponse
    {
        $this->authorize('update', $role->ministry);
        $role->delete();

        return back()->with('status', 'Role removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Ministry $ministry = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('ministries', 'name')->ignore($ministry?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'coordinator_id' => ['nullable', Rule::exists('users', 'id')],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['boolean'],
            'accepting_volunteers' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Ministry $ministry): array
    {
        return [
            'ministry' => $ministry,
            'coordinators' => User::permission('ministries.manage')->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
