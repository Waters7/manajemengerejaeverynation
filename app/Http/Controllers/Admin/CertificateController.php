<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CertificateKind;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Profile;
use App\Services\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Upload / remove certificates on a person's account (admin or church leader).
 */
class CertificateController extends Controller
{
    public function store(Request $request, Profile $profile, CertificateService $certificates): RedirectResponse
    {
        $this->authorize('manageCertificates', $profile);

        $data = $request->validate([
            'type' => ['required', Rule::enum(CertificateKind::class)],
            'discipleship_program_id' => ['nullable', 'required_if:type,program', Rule::exists('discipleship_programs', 'id')],
            'title' => ['nullable', 'string', 'max:190'],
            'issued_at' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $certificate = $certificates->upload($profile, $request->file('file'), collect($data)->except('file')->all(), $request->user());

        return redirect()->to(route('admin.members.show', $profile).'#files')
            ->with('status', "{$certificate->title} certificate uploaded to {$profile->displayName()}'s account.");
    }

    public function destroy(Certificate $certificate, CertificateService $certificates): RedirectResponse
    {
        $this->authorize('delete', $certificate);
        $profile = $certificate->profile;
        $certificates->delete($certificate);

        return redirect()->to(route('admin.members.show', $profile).'#files')->with('status', 'Certificate removed.');
    }
}
