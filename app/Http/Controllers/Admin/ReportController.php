<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvolvementStatus;
use App\Enums\NewcomerJourney;
use App\Enums\VolunteerStatus;
use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\DiscipleshipProgram;
use App\Models\DiscipleshipStage;
use App\Models\LifeGroup;
use App\Models\Ministry;
use App\Models\Profile;
use App\Services\AccessScope;
use App\Services\Exporter;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(): View
    {
        return view('admin.reports.index', ['catalog' => ReportService::catalog()]);
    }

    public function show(Request $request, string $report, AccessScope $scope): View
    {
        $definition = ReportService::catalog()[$report] ?? abort(404);
        $filters = $this->filters($request);
        $result = $this->reports->run($report, $request->user(), $filters);
        $user = $request->user();

        return view('admin.reports.show', [
            'key' => $report,
            'definition' => $definition,
            'result' => $result,
            'max' => max(1, (float) $result['rows']->max(fn ($row) => is_numeric($row[$result['bar']] ?? null) ? $row[$result['bar']] : 0)),
            'options' => [
                'campus' => Campus::when(! $scope->isChurchWide($user), fn ($q) => $q->whereIn('id', $scope->campusIds($user)))->orderBy('name')->pluck('name', 'id'),
                'lifegroup' => $scope->lifeGroups(LifeGroup::active(), $user)->orderBy('name')->pluck('name', 'id'),
                'leader' => Profile::whereIn('id', LifeGroup::whereNotNull('leader_profile_id')->select('leader_profile_id'))->orderBy('full_name')->pluck('full_name', 'id'),
                'program' => DiscipleshipProgram::orderBy('sequence')->pluck('name', 'id'),
                'stage' => DiscipleshipStage::ordered()->pluck('name', 'id'),
                'ministry' => $scope->ministries(Ministry::query(), $user)->orderBy('name')->pluck('name', 'id'),
                'status' => match ($report) {
                    'newcomers' => NewcomerJourney::options(),
                    'get-involved' => InvolvementStatus::options(),
                    'volunteer-participation' => VolunteerStatus::options(),
                    default => [],
                },
            ],
        ]);
    }

    public function export(Request $request, string $report, Exporter $exporter): StreamedResponse
    {
        $definition = ReportService::catalog()[$report] ?? abort(404);
        $result = $this->reports->run($report, $request->user(), $this->filters($request));

        return $exporter->download($definition['title'].'-'.now()->format('Ymd'), $request->input('format', 'xlsx'), $result['columns'], $result['rows']);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return array_filter($request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'campus' => ['nullable', 'integer'],
            'lifegroup' => ['nullable', 'integer'],
            'leader' => ['nullable', 'integer'],
            'program' => ['nullable', 'integer'],
            'stage' => ['nullable', 'integer'],
            'ministry' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:40'],
        ]), fn ($value) => $value !== null && $value !== '');
    }
}
