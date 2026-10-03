<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use App\Enums\FollowUpCategory;
use App\Enums\FollowUpTaskStatus;
use App\Http\Controllers\Controller;
use App\Models\FollowUpTask;
use App\Models\Profile;
use App\Models\User;
use App\Notifications\TeamAlert;
use App\Services\AccessScope;
use App\Services\AuditLogger;
use App\Services\CareRadar;
use App\Services\TeamNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function index(Request $request, CareRadar $radar): View
    {
        $user = $request->user();

        $tasks = $this->scope->followUpTasks(FollowUpTask::query(), $user)
            ->with(['profile', 'assignee', 'subject'])
            ->when($request->boolean('mine'), fn ($q) => $q->where('assigned_to', $user->id))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->input('status', 'pending') === 'pending', fn ($q) => $q->pending(), fn ($q) => $q->when($request->filled('status') && $request->input('status') !== 'all', fn ($q) => $q->where('status', $request->string('status'))))
            ->when($request->boolean('overdue'), fn ($q) => $q->whereDate('due_date', '<', today()))
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->paginate(30)
            ->withQueryString();

        return view('admin.follow-ups.index', [
            'tasks' => $tasks,
            'care' => $radar->sections($user, 4)->except('my_tasks'),
            'categories' => FollowUpCategory::options(),
            'statuses' => ['pending' => 'Open & in progress'] + FollowUpTaskStatus::options() + ['all' => 'All'],
            'team' => $this->team(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit, TeamNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'profile_id' => ['nullable', Rule::exists('profiles', 'id')],
            'title' => ['required', 'string', 'max:190'],
            'category' => ['required', Rule::enum(FollowUpCategory::class)],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['profile_id'] ?? null) {
            abort_unless($this->scope->canSeeProfile($request->user(), Profile::findOrFail($data['profile_id'])), 403);
        }

        $task = FollowUpTask::create($data + ['assigned_by' => $request->user()->id, 'status' => FollowUpTaskStatus::Open]);

        if ($task->assigned_to && $task->assigned_to !== $request->user()->id) {
            $audit->log(AuditAction::Assign, $task, "Follow-up “{$task->title}” assigned to {$task->assignee->name}");
            $notifier->toUser($task->assignee, new TeamAlert('follow_up', 'New follow-up assigned to you', $task->title, route('admin.follow-ups.index', ['mine' => 1])));
        }

        return back()->with('status', 'Follow-up scheduled.');
    }

    public function update(Request $request, FollowUpTask $task): RedirectResponse
    {
        $user = $request->user();
        abort_unless($task->assigned_to === $user->id || $this->scope->isChurchWide($user)
            || $this->scope->followUpTasks(FollowUpTask::whereKey($task->id), $user)->exists(), 403);

        $data = $request->validate([
            'status' => ['sometimes', Rule::enum(FollowUpTaskStatus::class)],
            'assigned_to' => ['sometimes', 'nullable', Rule::exists('users', 'id')],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if (($data['status'] ?? null) === FollowUpTaskStatus::Done->value) {
            $data['completed_at'] = now();
        }

        $task->update($data);

        return back()->with('status', 'Follow-up updated.');
    }

    /** @return Collection<int, string> */
    private function team()
    {
        return User::permission('followups.manage')->where('account_status', AccountStatus::Active->value)->orderBy('name')->pluck('name', 'id');
    }
}
