<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProgressStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BaptismRequest;
use App\Models\CurriculumChapter;
use App\Models\DiscipleshipProgram;
use App\Models\MemberProgramProgress;
use App\Models\Profile;
use App\Services\AccessScope;
use App\Services\JourneyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * A person's progress in one curriculum program.
 */
class ProgressController extends Controller
{
    public function store(Request $request, Profile $profile, JourneyService $journey): RedirectResponse
    {
        $this->authorize('disciple', $profile);
        $data = $request->validate([
            'program_id' => ['required', Rule::exists('discipleship_programs', 'id')],
            'expected_completion_at' => ['nullable', 'date'],
        ]);

        $program = DiscipleshipProgram::findOrFail($data['program_id']);
        $progress = $journey->startProgram($profile, $program, null, null, $data['expected_completion_at'] ?? null);

        $warning = $program->prerequisite && ! $profile->programProgress()->where('discipleship_program_id', $program->prerequisite_id)->where('status', ProgressStatus::Completed->value)->exists()
            ? " Note: the prerequisite {$program->prerequisite->name} is not completed yet."
            : '';

        return redirect()->route('admin.progress.show', $progress)->with('status', "{$program->name} started.{$warning}");
    }

    /** Water baptism status, recorded by the discipler or the discipleship team. */
    public function baptism(BaptismRequest $request, Profile $profile, JourneyService $journey): RedirectResponse
    {
        $this->authorize('disciple', $profile);
        $journey->recordBaptism($profile, $request->validated());

        return back()->with('status', 'Baptism status updated.');
    }

    public function show(Request $request, MemberProgramProgress $progress, AccessScope $scope): View
    {
        $this->authorize('view', $progress->profile);

        return view('admin.progress.show', [
            'progress' => $progress->load(['profile.activeLifeGroups', 'program.stage', 'program.prerequisite', 'discipler', 'batch']),
            'statuses' => ProgressStatus::options(),
            'disciplers' => $scope->profiles(Profile::members(), $request->user())->whereKeyNot($progress->profile_id)->orderBy('full_name')->pluck('full_name', 'id'),
            'canEdit' => $request->user()->can('disciple', $progress->profile),
        ]);
    }

    public function update(Request $request, MemberProgramProgress $progress, JourneyService $journey): RedirectResponse
    {
        $this->authorize('disciple', $progress->profile);
        $data = $request->validate([
            'status' => ['required', Rule::enum(ProgressStatus::class)],
            'discipler_profile_id' => ['nullable', Rule::exists('profiles', 'id')],
            'started_at' => ['nullable', 'date'],
            'expected_completion_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        abort_if(($data['discipler_profile_id'] ?? null) === $progress->profile_id, 422, 'A person cannot disciple themselves.');

        $progress->update(collect($data)->except(['status', 'completed_at'])->all());

        $status = ProgressStatus::from($data['status']);
        if ($status === ProgressStatus::Completed) {
            if ($progress->status !== ProgressStatus::Completed || ($data['completed_at'] ?? null)) {
                $journey->completeProgram($progress, $data['completed_at'] ?? null);
            }
        } elseif ($status !== $progress->status) {
            $journey->setStatus($progress, $status);
        }

        if ($progress->discipler_profile_id && $progress->wasChanged('discipler_profile_id')) {
            $journey->assignDiscipler($progress->profile, $progress->discipler);
        }

        return back()->with('status', 'Progress updated.');
    }

    public function chapter(Request $request, MemberProgramProgress $progress, CurriculumChapter $chapter, JourneyService $journey): RedirectResponse
    {
        $this->authorize('disciple', $progress->profile);
        abort_unless($chapter->discipleship_program_id === $progress->discipleship_program_id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::enum(ProgressStatus::class)],
            'completed_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);

        $journey->updateChapter($progress, $chapter, ProgressStatus::from($data['status']), $data['completed_on'] ?? null, $data['notes'] ?? null, $data['next_follow_up_at'] ?? null);

        return back();
    }
}
