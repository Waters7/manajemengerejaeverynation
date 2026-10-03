<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CertificateType;
use App\Enums\ProgramType;
use App\Http\Controllers\Controller;
use App\Models\CurriculumChapter;
use App\Models\DiscipleshipProgram;
use App\Models\DiscipleshipStage;
use App\Models\MemberChapterProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Curriculum programs (BOOK / CLASS / TRAINING / EVENT) and their chapters / sessions.
 */
class ProgramController extends Controller
{
    public function books(): View
    {
        return view('admin.curriculum.books', [
            'books' => DiscipleshipProgram::where('type', ProgramType::Book->value)->with('stage')
                ->withCount(['chapters', 'progress as readers_count' => fn ($q) => $q->where('status', 'in_progress'), 'progress as completed_count' => fn ($q) => $q->where('status', 'completed')])
                ->orderBy('sequence')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.curriculum.program', $this->formData(new DiscipleshipProgram([
            'discipleship_stage_id' => $request->integer('stage') ?: null,
            'type' => ProgramType::ClassProgram,
            'status' => 'active',
            'is_required' => true,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $program = DiscipleshipProgram::create($this->validated($request));
        $this->seedChapters($program, (int) $request->input('generate_chapters', 0));

        return redirect()->route('admin.curriculum.programs.edit', $program)->with('status', 'Program created.');
    }

    public function edit(DiscipleshipProgram $program): View
    {
        return view('admin.curriculum.program', $this->formData($program->load('chapters')));
    }

    public function update(Request $request, DiscipleshipProgram $program): RedirectResponse
    {
        $program->update($this->validated($request, $program));
        $this->seedChapters($program, (int) $request->input('generate_chapters', 0));

        return back()->with('status', 'Program updated.');
    }

    public function destroy(DiscipleshipProgram $program): RedirectResponse
    {
        abort_if($program->progress()->exists() || $program->batches()->exists(), 422, 'This program already has participants. Set it to inactive instead.');
        $program->delete();

        return redirect()->route('admin.curriculum.index')->with('status', 'Program deleted.');
    }

    public function storeChapter(Request $request, DiscipleshipProgram $program): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $number = ($program->chapters()->max('number') ?? 0) + 1;
        $program->chapters()->create($data + ['number' => $number, 'sequence' => $number]);
        $program->update(['total_sessions' => max($program->total_sessions, $number)]);

        return back()->with('status', 'Chapter added.');
    }

    public function updateChapter(Request $request, CurriculumChapter $chapter): RedirectResponse
    {
        $chapter->update($request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sequence' => ['required', 'integer', 'min:0', 'max:999'],
        ]));

        return back()->with('status', 'Chapter updated.');
    }

    public function destroyChapter(CurriculumChapter $chapter): RedirectResponse
    {
        abort_if(MemberChapterProgress::where('curriculum_chapter_id', $chapter->id)->exists(), 422, 'This chapter already has recorded progress. Rename it instead.');
        $chapter->delete();

        return back()->with('status', 'Chapter removed.');
    }

    private function seedChapters(DiscipleshipProgram $program, int $count): void
    {
        if ($count < 1 || $program->chapters()->exists()) {
            return;
        }
        $label = $program->type === ProgramType::Book ? 'Chapter' : 'Session';
        foreach (range(1, min($count, 60)) as $n) {
            $program->chapters()->create(['number' => $n, 'title' => "{$label} {$n}", 'sequence' => $n]);
        }
        $program->update(['total_sessions' => $count]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?DiscipleshipProgram $program = null): array
    {
        $data = $request->validate([
            'discipleship_stage_id' => ['required', Rule::exists('discipleship_stages', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::enum(ProgramType::class)],
            'sequence' => ['required', 'integer', 'min:0', 'max:99'],
            'prerequisite_id' => ['nullable', Rule::exists('discipleship_programs', 'id'), Rule::notIn([$program?->id])],
            'is_required' => ['boolean'],
            'is_milestone' => ['boolean'],
            'total_sessions' => ['nullable', 'integer', 'min:0', 'max:200'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'certificate_type' => ['required', Rule::enum(CertificateType::class)],
            'generate_chapters' => ['nullable', 'integer', 'min:0', 'max:60'],
        ]);

        return collect($data)->except('generate_chapters')->map(fn ($v, $k) => $k === 'total_sessions' ? (int) $v : $v)->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(DiscipleshipProgram $program): array
    {
        return [
            'program' => $program,
            'stages' => DiscipleshipStage::orderBy('sequence')->pluck('name', 'id'),
            'types' => ProgramType::options(),
            'certificateTypes' => CertificateType::options(),
            'prerequisites' => DiscipleshipProgram::whereKeyNot($program->id)->orderBy('sequence')->pluck('name', 'id'),
        ];
    }
}
