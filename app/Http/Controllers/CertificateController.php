<?php

namespace App\Http\Controllers;

use App\Enums\CertificateType;
use App\Models\Certificate;
use App\Models\DiscipleshipProgram;
use App\Models\Profile;
use App\Services\CertificateService;
use App\Services\Settings;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Opening certificates: by the person themselves (member area) or by the team caring for them.
 */
class CertificateController extends Controller
{
    /** Printable certificate (one-time program certificates are rendered by the app). */
    public function show(Certificate $certificate, Settings $settings): View|BinaryFileResponse
    {
        $this->authorize('view', $certificate);

        if ($certificate->hasFile()) {
            return $this->file($certificate);
        }

        return view('certificates.print', [
            'settings' => $settings,
            'profile' => $certificate->profile,
            'heading' => 'Certificate of Completion',
            'title' => $certificate->title,
            'body' => 'has faithfully completed',
            'date' => $certificate->issued_at,
            'number' => $certificate->certificate_number,
            'stats' => [],
        ]);
    }

    /** Download / open an uploaded certificate file (private storage). */
    public function file(Certificate $certificate): BinaryFileResponse
    {
        $this->authorize('view', $certificate);
        abort_unless($certificate->hasFile() && Storage::disk(CertificateService::DISK)->exists($certificate->file_path), 404);

        return response()->file(Storage::disk(CertificateService::DISK)->path($certificate->file_path), [
            'Content-Disposition' => 'inline; filename="'.addslashes($certificate->file_name ?: $certificate->certificate_number).'"',
        ]);
    }

    /** Journey record for a repeatable program: how many times completed, for yourself and for others. */
    public function journeyRecord(Profile $profile, DiscipleshipProgram $program, CertificateService $certificates, Settings $settings): View
    {
        $this->authorize('viewRecords', $profile);
        abort_unless($program->certificate_type === CertificateType::Count, 404);

        $record = $certificates->journeyRecords($profile)->firstWhere('program.id', $program->id);
        abort_unless($record && ($record['self'] > 0 || $record['led'] > 0), 404);

        return view('certificates.print', [
            'settings' => $settings,
            'profile' => $profile,
            'heading' => 'Discipleship Journey Record',
            'title' => $program->name,
            'body' => $record['self'] > 0 ? 'has walked through' : 'has helped others walk through',
            'date' => $record['completed_at'] ?? today(),
            'number' => 'ENB-'.$certificates->code($program->name).'-'.str_pad((string) $profile->id, 5, '0', STR_PAD_LEFT),
            'stats' => [
                'Completed personally' => $record['self'].'×',
                'Led others through it' => $record['led'].'×',
                'Currently leading' => $record['leading'],
            ],
        ]);
    }
}
