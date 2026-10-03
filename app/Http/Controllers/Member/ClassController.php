<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Services\ClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->ensureProfile();

        $participations = $profile->classParticipations()
            ->with(['batch.program', 'batch.sessions', 'attendances'])
            ->latest()
            ->get();

        $open = ClassBatch::upcoming()
            ->where('registration_status', 'open')
            ->with(['program.stage', 'facilitator'])
            ->withCount('activeParticipants')
            ->whereNotIn('id', $participations->pluck('class_batch_id'))
            ->orderBy('start_date')
            ->get()
            ->filter(fn (ClassBatch $batch) => $batch->capacity === null || $batch->active_participants_count < $batch->capacity);

        return view('member.classes', ['participations' => $participations, 'open' => $open]);
    }

    public function register(Request $request, ClassBatch $batch, ClassService $classes): RedirectResponse
    {
        abort_unless($batch->isOpenForRegistration(), 422, 'Registration for this class is closed.');

        $classes->enroll($batch, $request->user()->ensureProfile());

        return back()->with('status', "Kamu terdaftar di {$batch->fullLabel()}.");
    }
}
