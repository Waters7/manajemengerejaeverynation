{{--
    Water baptism card for a person's discipleship journey.
    $profile: Profile
    $action (optional): form URL — when set and the viewer may disciple this person, the card is editable.
--}}
@php
    $status = $profile->baptism_status ?? \App\Enums\BaptismStatus::NotYet;
    $editable = isset($action) && auth()->user()?->can('disciple', $profile);
@endphp
<div x-data="{ edit: @js($errors->hasAny(['baptism_status', 'baptism_date', 'baptism_place', 'baptism_notes'])), status: @js(old('baptism_status', $status->value)) }" @class([
    'rounded-2xl border p-5',
    'border-green-200 bg-green-50/60' => $status === \App\Enums\BaptismStatus::Baptized,
    'border-amber-200 bg-amber-50/60' => $status === \App\Enums\BaptismStatus::Scheduled,
    'border-line bg-white' => $status === \App\Enums\BaptismStatus::NotYet,
])>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <span @class([
                'grid size-10 shrink-0 place-items-center rounded-full',
                'bg-success text-white' => $status === \App\Enums\BaptismStatus::Baptized,
                'bg-warning text-white' => $status === \App\Enums\BaptismStatus::Scheduled,
                'bg-slate-100 text-slate-500' => $status === \App\Enums\BaptismStatus::NotYet,
            ])>
                @if ($status === \App\Enums\BaptismStatus::Baptized)
                    <x-icon name="check" class="size-5" />
                @else
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3.5c-3 4-6 7.2-6 10.5a6 6 0 0 0 12 0c0-3.3-3-6.5-6-10.5Z" /></svg>
                @endif
            </span>
            <div>
                <p class="text-sm font-extrabold tracking-[0.15em] uppercase">Water baptism</p>
                <p class="mt-0.5 font-bold">{{ $status->label() }}</p>
                @if ($status !== \App\Enums\BaptismStatus::NotYet && ($profile->baptism_date || $profile->baptism_place))
                    <p class="text-sm text-muted">
                        {{ $profile->baptism_date?->translatedFormat('j F Y') }}{{ $profile->baptism_date && $profile->baptism_place ? ' · ' : '' }}{{ $profile->baptism_place }}
                    </p>
                @endif
                @if ($editable && $profile->baptism_notes)
                    <p class="mt-1 text-xs whitespace-pre-line text-muted">{{ $profile->baptism_notes }}</p>
                @endif
            </div>
        </div>
        @if ($editable)
            <button type="button" x-on:click="edit = !edit" class="btn btn-outline btn-sm" x-text="edit ? 'Close' : 'Update'">Update</button>
        @endif
    </div>

    @if ($editable)
        <form x-show="edit" x-cloak method="POST" action="{{ $action }}" class="mt-5 grid gap-4 border-t border-line/70 pt-5 sm:grid-cols-2">
            @csrf @method('PATCH')
            <fieldset class="sm:col-span-2">
                <legend class="label">Status</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Enums\BaptismStatus::options() as $value => $label)
                        <label class="chip"><input type="radio" name="baptism_status" value="{{ $value }}" class="sr-only" x-model="status"> {{ $label }}</label>
                    @endforeach
                </div>
                @error('baptism_status')<p class="error">{{ $message }}</p>@enderror
            </fieldset>
            <div x-show="status !== 'not_yet'" x-cloak>
                <x-form.input name="baptism_date" type="date" label="Tanggal" :value="$profile->baptism_date" x-bind:required="status === 'scheduled'" />
            </div>
            <div x-show="status !== 'not_yet'" x-cloak>
                <x-form.input name="baptism_place" label="Tempat / gereja" :value="$profile->baptism_place" placeholder="Every Nation Bekasi" />
            </div>
            <x-form.textarea name="baptism_notes" label="Catatan" :value="$profile->baptism_notes" rows="2" class="sm:col-span-2" />
            <div class="sm:col-span-2"><button class="btn btn-primary btn-sm">Save baptism status</button></div>
        </form>
    @endif
</div>
