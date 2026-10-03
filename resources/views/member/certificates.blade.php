<x-layouts.member title="Certificates">
    <x-page-header title="My certificates" description="Sertifikat baptisan, sertifikat Leadership, dan catatan perjalanan pemuridanmu." />

    {{-- Water baptism --}}
    <section class="card card-pad">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="eyebrow">Water baptism</p>
                <p class="mt-1 text-xl font-extrabold">{{ $profile->baptism_status?->label() ?? 'Belum dibaptis' }}</p>
                @if ($profile->baptism_date)<p class="text-sm text-muted">{{ $profile->baptism_date->translatedFormat('j F Y') }}{{ $profile->baptism_place ? ' · '.$profile->baptism_place : '' }}</p>@endif
            </div>
            <div class="flex flex-wrap gap-2">
                @forelse ($baptismCertificates as $certificate)
                    <a href="{{ route('certificates.file', $certificate) }}" target="_blank" class="btn btn-primary btn-sm"><x-icon name="download" class="size-4" /> {{ $certificate->title }} certificate</a>
                @empty
                    <span class="text-sm text-muted">Sertifikat baptisan akan muncul di sini setelah diunggah oleh tim gereja.</span>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Leadership (one-time) certificates --}}
    <h2 class="mt-10 text-lg font-extrabold uppercase">Leadership certificates</h2>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        @foreach ($oneTime as $row)
            <div @class(['card card-pad flex items-center justify-between gap-4', 'opacity-70' => ! $row['certificate']])>
                <div class="flex items-center gap-4">
                    <span @class(['grid size-12 place-items-center rounded-2xl', 'bg-brand text-white' => $row['certificate'], 'bg-slate-100 text-slate-400' => ! $row['certificate']])><x-icon name="academic" class="size-6" /></span>
                    <div>
                        <p class="text-lg font-extrabold">{{ $row['program']->name }}</p>
                        @if ($row['certificate'])
                            <p class="text-xs text-muted">{{ $row['certificate']->issued_at->translatedFormat('j F Y') }} · {{ $row['certificate']->certificate_number }}</p>
                        @else
                            <p class="text-xs text-muted">{{ $row['status'] === \App\Enums\ProgressStatus::InProgress ? 'Sedang diikuti' : 'Belum diikuti' }} — sertifikat terbit setelah kelas selesai.</p>
                        @endif
                    </div>
                </div>
                @if ($row['certificate'])
                    <a href="{{ route('certificates.show', $row['certificate']) }}" target="_blank" class="btn btn-primary btn-sm"><x-icon name="download" class="size-4" /> Certificate</a>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Repeatable journey: counts --}}
    <h2 class="mt-10 text-lg font-extrabold uppercase">Discipleship journey record</h2>
    <p class="mt-1 text-sm text-muted">Program pemuridan dapat diulang — untuk dirimu sendiri dan saat kamu memuridkan orang lain. Di sini tercatat berapa kali.</p>
    <div class="card mt-4 overflow-x-auto">
        <table class="table">
            <thead><tr><th>Program</th><th>Stage</th><th class="text-right">For myself</th><th class="text-right">Led others</th><th class="text-right">Leading now</th><th></th></tr></thead>
            <tbody>
                @foreach ($records as $record)
                    <tr @class(['text-slate-400' => $record['self'] === 0 && $record['led'] === 0])>
                        <td class="font-bold">{{ $record['program']->name }}</td>
                        <td class="text-sm">{{ $record['program']->stage?->name }}</td>
                        <td class="text-right font-extrabold tabular-nums">{{ $record['self'] }}×</td>
                        <td class="text-right font-extrabold tabular-nums">{{ $record['led'] }}×</td>
                        <td class="text-right tabular-nums">{{ $record['leading'] }}</td>
                        <td class="text-right">
                            @if ($record['self'] > 0 || $record['led'] > 0)
                                <a href="{{ route('certificates.journey-record', [$profile, $record['program']]) }}" target="_blank" class="btn btn-outline btn-sm"><x-icon name="download" class="size-4" /> Record</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($otherCertificates->isNotEmpty())
        <h2 class="mt-10 text-lg font-extrabold uppercase">Other documents</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @foreach ($otherCertificates as $certificate)
                <a href="{{ route('certificates.file', $certificate) }}" target="_blank" class="card card-hover flex items-center justify-between gap-3 p-4">
                    <span><strong>{{ $certificate->title }}</strong><span class="block text-xs text-muted">{{ $certificate->issued_at->translatedFormat('j F Y') }}</span></span>
                    <x-icon name="download" class="size-5 text-brand" />
                </a>
            @endforeach
        </div>
    @endif
</x-layouts.member>
