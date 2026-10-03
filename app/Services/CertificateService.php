<?php

namespace App\Services;

use App\Enums\CertificateKind;
use App\Enums\CertificateType;
use App\Enums\ParticipantStatus;
use App\Enums\ProgressStatus;
use App\Models\Certificate;
use App\Models\ClassParticipant;
use App\Models\DiscipleshipProgram;
use App\Models\MemberProgramProgress;
use App\Models\Profile;
use App\Models\User;
use App\Notifications\TeamAlert;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Certificates and journey records.
 *
 * - Repeatable journey programs (One 2 One … Making Disciples 2) are recorded as counts:
 *   how many times a person completed it themselves and how many people they led through it.
 * - One-time programs (Leadership 113 / 215) issue a numbered certificate on completion.
 * - Baptism (and other) certificates are uploaded by the church team as private files.
 */
class CertificateService
{
    public const DISK = 'local';

    public function __construct(
        private TimelineRecorder $timeline,
        private TeamNotifier $notifier,
    ) {}

    /**
     * Journey record per repeatable program.
     *
     * @return Collection<int, array{program: DiscipleshipProgram, self: int, led: int, leading: int, completed_at: ?Carbon}>
     */
    public function journeyRecords(Profile $profile): Collection
    {
        $programs = DiscipleshipProgram::query()
            ->active()
            ->where('certificate_type', CertificateType::Count->value)
            ->with('stage')
            ->get()
            ->sortBy(fn (DiscipleshipProgram $p) => [$p->stage?->sequence, $p->sequence])
            ->values();

        $own = $profile->programProgress()->get()->keyBy('discipleship_program_id');

        $ownClasses = ClassParticipant::query()
            ->join('class_batches', 'class_batches.id', '=', 'class_participants.class_batch_id')
            ->where('class_participants.profile_id', $profile->id)
            ->where('class_participants.status', ParticipantStatus::Completed->value)
            ->selectRaw('class_batches.discipleship_program_id as program_id, count(*) as total')
            ->groupBy('class_batches.discipleship_program_id')
            ->pluck('total', 'program_id');

        $ledPersonally = MemberProgramProgress::query()
            ->where('discipler_profile_id', $profile->id)
            ->where('profile_id', '!=', $profile->id)
            ->selectRaw('discipleship_program_id, status, count(*) as total')
            ->groupBy('discipleship_program_id', 'status')
            ->get();

        $ledInClass = ClassParticipant::query()
            ->join('class_batches', 'class_batches.id', '=', 'class_participants.class_batch_id')
            ->where('class_batches.facilitator_profile_id', $profile->id)
            ->where('class_participants.profile_id', '!=', $profile->id)
            ->where('class_participants.status', ParticipantStatus::Completed->value)
            ->selectRaw('class_batches.discipleship_program_id as program_id, count(*) as total')
            ->groupBy('class_batches.discipleship_program_id')
            ->pluck('total', 'program_id');

        return $programs->map(function (DiscipleshipProgram $program) use ($own, $ownClasses, $ledPersonally, $ledInClass) {
            $mine = $own->get($program->id);
            $selfFromProgress = $mine?->status === ProgressStatus::Completed ? 1 : 0;
            $statusValue = fn ($row) => $row->status instanceof \BackedEnum ? $row->status->value : $row->status;

            return [
                'program' => $program,
                'self' => max($selfFromProgress, (int) ($ownClasses[$program->id] ?? 0)),
                'led' => (int) $ledPersonally->where('discipleship_program_id', $program->id)->filter(fn ($r) => $statusValue($r) === ProgressStatus::Completed->value)->sum('total')
                    + (int) ($ledInClass[$program->id] ?? 0),
                'leading' => (int) $ledPersonally->where('discipleship_program_id', $program->id)->filter(fn ($r) => $statusValue($r) === ProgressStatus::InProgress->value)->sum('total'),
                'completed_at' => $mine?->completed_at,
            ];
        });
    }

    /** Issue the one-time certificate for a completed program (Leadership 113 / 215). */
    public function issueProgramCertificate(MemberProgramProgress $progress): ?Certificate
    {
        $program = $progress->program;
        if ($program?->certificate_type !== CertificateType::Once || $progress->status !== ProgressStatus::Completed) {
            return null;
        }

        $existing = Certificate::where('profile_id', $progress->profile_id)->where('discipleship_program_id', $program->id)->first();
        if ($existing) {
            return $existing;
        }

        $certificate = Certificate::create([
            'profile_id' => $progress->profile_id,
            'type' => CertificateKind::Program,
            'discipleship_program_id' => $program->id,
            'certificate_number' => $this->nextNumber($this->code($program->name)),
            'title' => $program->name,
            'issued_at' => $progress->completed_at ?? today(),
            'issued_by' => Auth::id(),
        ]);

        $this->timeline->record($progress->profile, 'certificate', "Received the {$program->name} certificate", $certificate->certificate_number, $certificate);
        $this->notifyOwner($progress->profile, 'Your certificate is ready', "Your {$program->name} certificate is available in your account.");

        return $certificate;
    }

    /** Issue any one-time certificates a person has earned but not yet received (e.g. older records). */
    public function syncOneTimeCertificates(Profile $profile): void
    {
        MemberProgramProgress::with('program', 'profile')
            ->where('profile_id', $profile->id)
            ->where('status', ProgressStatus::Completed->value)
            ->whereHas('program', fn ($q) => $q->where('certificate_type', CertificateType::Once->value))
            ->whereDoesntHave('program.certificates', fn ($q) => $q->where('profile_id', $profile->id))
            ->get()
            ->each(fn (MemberProgramProgress $progress) => $this->issueProgramCertificate($progress));
    }

    /**
     * Upload a certificate file (baptism, scanned program certificate or other) to a person's account.
     *
     * @param  array{type: string, title?: ?string, issued_at?: ?string, notes?: ?string, discipleship_program_id?: ?int}  $data
     */
    public function upload(Profile $profile, UploadedFile $file, array $data, User $by): Certificate
    {
        return DB::transaction(function () use ($profile, $file, $data, $by) {
            $kind = CertificateKind::from($data['type']);
            $program = isset($data['discipleship_program_id']) ? DiscipleshipProgram::find($data['discipleship_program_id']) : null;
            $path = $file->storeAs("certificates/{$profile->id}", Str::ulid().'.'.$file->guessExtension(), self::DISK);

            $existing = $kind === CertificateKind::Program && $program
                ? Certificate::where('profile_id', $profile->id)->where('discipleship_program_id', $program->id)->first()
                : null;

            if ($existing) {
                if ($existing->file_path) {
                    Storage::disk(self::DISK)->delete($existing->file_path);
                }
                $existing->update([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'notes' => $data['notes'] ?? $existing->notes,
                    'issued_at' => $data['issued_at'] ?? $existing->issued_at,
                ]);

                return $existing;
            }

            $title = $data['title'] ?? null;
            $certificate = Certificate::create([
                'profile_id' => $profile->id,
                'type' => $kind,
                'discipleship_program_id' => $kind === CertificateKind::Program ? $program?->id : null,
                'certificate_number' => $this->nextNumber(match ($kind) {
                    CertificateKind::Baptism => 'BAP',
                    CertificateKind::Program => $program ? $this->code($program->name) : 'PRG',
                    CertificateKind::Other => 'DOC',
                }),
                'title' => $title ?: ($kind === CertificateKind::Baptism ? 'Water Baptism' : ($program?->name ?? 'Certificate')),
                'issued_at' => $data['issued_at'] ?? ($kind === CertificateKind::Baptism ? $profile->baptism_date : null) ?? today(),
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'notes' => $data['notes'] ?? null,
                'issued_by' => $by->id,
            ]);

            $this->timeline->record($profile, 'certificate', "{$certificate->title} certificate added", null, $certificate);
            $this->notifyOwner($profile, 'A new certificate was added', "{$certificate->title} is available in your account.");

            return $certificate;
        });
    }

    public function delete(Certificate $certificate): void
    {
        if ($certificate->file_path) {
            Storage::disk(self::DISK)->delete($certificate->file_path);
        }
        $certificate->delete();
    }

    /** "Leadership 113" → L113, "Making Disciples 2" → MD2, "One 2 One" → O2O. */
    public function code(string $name): string
    {
        return Str::upper(collect(preg_split('/\s+/', trim($name)))
            ->map(fn ($word) => ctype_digit($word) ? $word : mb_substr($word, 0, 1))
            ->implode('')) ?: 'CRT';
    }

    private function nextNumber(string $code): string
    {
        $year = now()->year;
        $sequence = Certificate::where('certificate_number', 'like', "ENB-{$code}-{$year}-%")->count() + 1;

        do {
            $number = sprintf('ENB-%s-%d-%04d', $code, $year, $sequence++);
        } while (Certificate::where('certificate_number', $number)->exists());

        return $number;
    }

    private function notifyOwner(Profile $profile, string $title, string $message): void
    {
        $this->notifier->toUser($profile->user, new TeamAlert('certificate', $title, $message, route('member.certificates')));
    }
}
