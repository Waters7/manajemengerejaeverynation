<?php

namespace App\Livewire;

use App\Enums\ProgressStatus;
use App\Models\CurriculumChapter;
use App\Models\MemberProgramProgress;
use App\Services\JourneyService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Chapter / lesson checklist for a person's program (One 2 One, Purple Book…).
 * Used by disciplers (member area) and the ministry dashboard.
 */
class ProgramTracker extends Component
{
    #[Locked]
    public int $progressId;

    public ?string $nextFollowUp = null;

    /** @var array<int, string> chapter_id => notes */
    public array $notes = [];

    public ?int $editing = null;

    public function mount(MemberProgramProgress $progress): void
    {
        $this->authorize('disciple', $progress->profile);
        $this->progressId = $progress->id;
        $this->nextFollowUp = $progress->next_follow_up_at?->toDateString();
        $this->notes = $progress->chapterProgress()->pluck('notes', 'curriculum_chapter_id')->map(fn ($n) => (string) $n)->all();
    }

    public function toggle(int $chapterId, JourneyService $journey): void
    {
        $progress = $this->progress();
        $chapter = CurriculumChapter::where('discipleship_program_id', $progress->discipleship_program_id)->findOrFail($chapterId);

        $current = $progress->chapterProgress->firstWhere('curriculum_chapter_id', $chapterId)?->status;
        $next = $current === ProgressStatus::Completed ? ProgressStatus::NotStarted : ProgressStatus::Completed;

        $journey->updateChapter($progress, $chapter, $next, null, $this->notes[$chapterId] ?? null, $this->nextFollowUp);
    }

    public function saveNote(int $chapterId, JourneyService $journey): void
    {
        $progress = $this->progress();
        $chapter = CurriculumChapter::where('discipleship_program_id', $progress->discipleship_program_id)->findOrFail($chapterId);
        $this->validate(["notes.{$chapterId}" => ['nullable', 'string', 'max:2000']]);

        $status = $progress->chapterProgress->firstWhere('curriculum_chapter_id', $chapterId)?->status ?? ProgressStatus::InProgress;
        $journey->updateChapter($progress, $chapter, $status, null, $this->notes[$chapterId] ?? null, $this->nextFollowUp);
        $this->editing = null;
    }

    public function saveFollowUp(): void
    {
        $this->validate(['nextFollowUp' => ['nullable', 'date']]);
        $this->progress()->update(['next_follow_up_at' => $this->nextFollowUp ?: null, 'last_activity_at' => now()]);
        $this->dispatch('saved');
    }

    private function progress(): MemberProgramProgress
    {
        $progress = MemberProgramProgress::with(['profile', 'chapterProgress', 'program.chapters'])->findOrFail($this->progressId);
        $this->authorize('disciple', $progress->profile);

        return $progress;
    }

    public function render(): View
    {
        $progress = MemberProgramProgress::with(['program.chapters', 'chapterProgress', 'discipler'])->findOrFail($this->progressId);

        return view('livewire.program-tracker', [
            'progress' => $progress,
            'chapters' => $progress->program->chapters,
            'rows' => $progress->chapterProgress->keyBy('curriculum_chapter_id'),
        ]);
    }
}
