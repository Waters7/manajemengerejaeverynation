<?php

namespace App\Http\Controllers\Member;

use App\Enums\CertificateKind;
use App\Enums\CertificateType;
use App\Enums\ProgressStatus;
use App\Http\Controllers\Controller;
use App\Models\DiscipleshipProgram;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * My certificates: baptism certificate, Leadership 113 / 215 certificates and the journey record.
 */
class CertificateController extends Controller
{
    public function __invoke(Request $request, CertificateService $certificates): View
    {
        $profile = $request->user()->ensureProfile();
        $certificates->syncOneTimeCertificates($profile);
        $request->user()->unreadNotifications()->where('data->category', 'certificate')->update(['read_at' => now()]);

        $all = $profile->certificates()->with('program')->get();
        $progress = $profile->programProgress()->get()->keyBy('discipleship_program_id');

        return view('member.certificates', [
            'profile' => $profile,
            'baptismCertificates' => $all->where('type', CertificateKind::Baptism),
            'otherCertificates' => $all->where('type', CertificateKind::Other),
            'oneTime' => DiscipleshipProgram::active()->where('certificate_type', CertificateType::Once->value)->orderBy('sequence')->get()
                ->map(fn (DiscipleshipProgram $program) => [
                    'program' => $program,
                    'certificate' => $all->firstWhere('discipleship_program_id', $program->id),
                    'status' => $progress->get($program->id)?->status ?? ProgressStatus::NotStarted,
                ]),
            'records' => $certificates->journeyRecords($profile),
        ]);
    }
}
