<?php

namespace App\Http\Controllers\Member;

use App\Enums\ContactType;
use App\Enums\ProgressStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DiscipleshipMeetingRequest;
use App\Models\CurriculumChapter;
use App\Models\DisciplerRelationship;
use App\Models\DiscipleshipProgram;
use App\Models\MemberProgramProgress;
use App\Models\Profile;
use App\Services\JourneyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Discipler dashboard — MY DISCIPLES. Available to anyone who is actively discipling someone.
 */
class DiscipleController extends Controller
{
    public function index(Request $request): View
    {
        $me = $request->user()->ensureProfile();

        $relationships = DisciplerRelationship::active()
            ->where('discipler_profile_id', $me->id)
            ->with(['disciple.currentStage', 'disciple.currentProgram', 'disciple.activeLifeGroups', 'latestMeeting'])
            ->get();

        $progress = MemberProgramProgress::with(['program.chapters', 'chapterProgress'])
            ->whereIn('profile_id', $relationships->pluck('disciple_profile_id'))
            ->where('status', ProgressStatus::InProgress->value)
            ->get()
            ->groupBy('profile_id');

        return view('member.disciples.index', ['relationships' => $relationships, 'progress' => $progress]);
    }

    public function show(Request $request, Profile $profile, JourneyService $journey): View
    {
        $this->authorize('disciple', $profile);

        $relationship = DisciplerRelationship::active()
            ->where('disciple_profile_id', $profile->id)
            ->where('discipler_profile_id', $request->user()->profile?->id)
            ->with('meetings')
            ->first();

        return view('member.disciples.show', [
            'profile' => $profile->load('currentStage', 'currentProgram', 'activeLifeGroups'),
            'relationship' => $relationship,
            'stages' => $journey->journey($profile),
            'activeProgress' => $profile->programProgress()->where('status', ProgressStatus::InProgress->value)->with('program')->get(),
            'notes' => $profile->followUps()->with('author')->latest()->limit(15)->get(),
            'programs' => $journey->orderedPrograms(),
        ]);
    }

    public function meeting(DiscipleshipMeetingRequest $request, Profile $profile, JourneyService $journey): RedirectResponse
    {
        $this->authorize('disciple', $profile);

        $relationship = DisciplerRelationship::active()
            ->where('disciple_profile_id', $profile->id)
            ->where('discipler_profile_id', $request->user()->profile?->id)
            ->firstOrFail();

        $journey->recordMeeting($relationship, $request->validated());

        return back()->with('status', 'Meeting recorded.');
    }

    public function note(Request $request, Profile $profile): RedirectResponse
    {
        $this->authorize('disciple', $profile);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000'], 'type' => ['required', Rule::enum(ContactType::class)]]);

        $profile->followUps()->create($data + ['user_id' => $request->user()->id, 'is_internal' => true]);

        return back()->with('status', 'Note saved.');
    }

    public function startProgram(Request $request, Profile $profile, JourneyService $journey): RedirectResponse
    {
        $this->authorize('disciple', $profile);
        $data = $request->validate([
            'program_id' => ['required', Rule::exists('discipleship_programs', 'id')],
            'expected_completion_at' => ['nullable', 'date', 'after:today'],
        ]);

        $journey->startProgram($profile, DiscipleshipProgram::findOrFail($data['program_id']), $request->user()->profile, null, $data['expected_completion_at'] ?? null);

        return back()->with('status', 'Program started.');
    }

    public function chapter(Request $request, Profile $profile, MemberProgramProgress $progress, CurriculumChapter $chapter, JourneyService $journey): RedirectResponse
    {
        $this->authorize('disciple', $profile);
        abort_unless($progress->profile_id === $profile->id && $chapter->discipleship_program_id === $progress->discipleship_program_id, 404);

        $data = $request->validate(['status' => ['required', Rule::enum(ProgressStatus::class)], 'notes' => ['nullable', 'string', 'max:2000']]);
        $journey->updateChapter($progress, $chapter, ProgressStatus::from($data['status']), null, $data['notes'] ?? null);

        return back();
    }
}
