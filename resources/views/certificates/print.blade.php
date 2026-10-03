{{--
    Printable A4 landscape certificate. "Print / Save as PDF" uses the browser's print dialog.
    Variables: $profile, $heading, $title, $body, $date, $number, $stats (label => value), $settings
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $heading }} — {{ $title }} — {{ $profile->full_name }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @fonts
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4 landscape; margin: 0; }
        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .sheet { box-shadow: none !important; margin: 0 !important; }
        }
        .sheet { width: 297mm; min-height: 210mm; }
    </style>
</head>
<body class="bg-slate-200 py-8 print:py-0">
    <div class="no-print mx-auto mb-6 flex max-w-[297mm] flex-wrap items-center justify-between gap-3 px-4">
        <a href="{{ url()->previous() }}" class="btn btn-white btn-sm"><x-icon name="arrow-left" class="size-4" /> Back</a>
        <button type="button" onclick="window.print()" class="btn btn-primary btn-sm"><x-icon name="download" class="size-4" /> Print / Save as PDF</button>
    </div>

    <div class="sheet relative mx-auto overflow-hidden bg-white shadow-2xl">
        <div class="absolute inset-0 border-[14px] border-brand"></div>
        <div class="absolute inset-[22px] border border-brand/40"></div>
        <img src="{{ asset('images/mark-blue.png') }}" alt="" class="pointer-events-none absolute -right-24 -bottom-24 w-[150mm] opacity-[0.05]">

        <div class="relative flex min-h-[210mm] flex-col items-center justify-between px-[28mm] py-[22mm] text-center">
            <div class="flex flex-col items-center">
                <img src="{{ asset('images/logo-blue.png') }}" alt="{{ $settings->get('site_name') }}" class="h-[22mm] w-auto">
                <p class="mt-6 text-sm font-bold tracking-[0.5em] text-brand uppercase">{{ $heading }}</p>
            </div>

            <div>
                <p class="text-base text-slate-500">This is to certify that</p>
                <p class="mt-3 text-[2.6rem] leading-tight font-extrabold tracking-tight text-ink">{{ $profile->full_name }}</p>
                <div class="mx-auto my-4 h-1 w-24 rounded-full bg-brand"></div>
                <p class="text-base text-slate-500">{{ $body }}</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-brand uppercase">{{ $title }}</p>

                @if (! empty($stats))
                    <div class="mx-auto mt-7 grid max-w-xl gap-4" style="grid-template-columns: repeat({{ count($stats) }}, minmax(0, 1fr))">
                        @foreach ($stats as $label => $value)
                            <div class="rounded-2xl border border-brand/20 bg-brand-50/60 px-4 py-3">
                                <p class="text-2xl font-extrabold text-ink">{{ $value }}</p>
                                <p class="text-[0.65rem] font-bold tracking-wider text-muted uppercase">{{ $label }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="grid w-full grid-cols-3 items-end gap-8 text-sm">
                <div class="text-left">
                    <p class="font-bold text-ink">{{ $date?->translatedFormat('j F Y') }}</p>
                    <p class="text-xs text-muted">Bekasi</p>
                </div>
                <div>
                    <p class="text-xs font-bold tracking-[0.3em] text-brand uppercase">Honor God. Make Disciples.</p>
                    <p class="mt-1 font-mono text-[0.65rem] text-muted">{{ $number }}</p>
                </div>
                <div class="text-right">
                    <div class="ml-auto h-10 w-48 border-b border-slate-400"></div>
                    <p class="mt-1 font-bold text-ink">Senior Pastor</p>
                    <p class="text-xs text-muted">{{ $settings->get('site_name') }}</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
