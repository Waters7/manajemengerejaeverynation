<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscipleshipStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * DISCIPLESHIP → Curriculum. Stages and programs are fully configurable.
 */
class CurriculumController extends Controller
{
    public function index(): View
    {
        return view('admin.curriculum.index', [
            'stages' => DiscipleshipStage::orderBy('sequence')
                ->with(['programs' => fn ($q) => $q->withCount(['chapters', 'batches', 'progress as active_count' => fn ($p) => $p->where('status', 'in_progress')])->with('prerequisite')])
                ->get(),
        ]);
    }

    public function storeStage(Request $request): RedirectResponse
    {
        DiscipleshipStage::create($this->validated($request) + ['sequence' => (DiscipleshipStage::max('sequence') ?? 0) + 1]);

        return back()->with('status', 'Stage added.');
    }

    public function updateStage(Request $request, DiscipleshipStage $stage): RedirectResponse
    {
        $stage->update($this->validated($request, $stage));

        return back()->with('status', 'Stage updated.');
    }

    public function destroyStage(DiscipleshipStage $stage): RedirectResponse
    {
        abort_if($stage->programs()->exists(), 422, 'Move or delete the programs in this stage first.');
        $stage->delete();

        return back()->with('status', 'Stage removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?DiscipleshipStage $stage = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('discipleship_stages', 'name')->ignore($stage?->id)],
            'tagline' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sequence' => ['sometimes', 'integer', 'min:0', 'max:99'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
