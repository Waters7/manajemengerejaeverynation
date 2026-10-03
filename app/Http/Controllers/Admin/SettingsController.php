<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\AuditLogger;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SYSTEM → Settings: church details and WhatsApp message templates.
 */
class SettingsController extends Controller
{
    private const GROUPS = ['general', 'whatsapp'];

    public function edit(): View
    {
        return view('admin.settings', [
            'groups' => collect(Settings::definitions())->only(self::GROUPS),
            'stored' => SiteSetting::pluck('value', 'key'),
        ]);
    }

    public function update(Request $request, Settings $settings, AuditLogger $audit): RedirectResponse
    {
        $keys = collect(Settings::definitions())->only(self::GROUPS)->collapse()->keys();
        $data = $request->validate($keys->mapWithKeys(fn ($key) => [$key => ['nullable', 'string', 'max:5000']])->all());

        $settings->save($keys->mapWithKeys(fn ($key) => [$key => $data[$key] ?? null])->all());
        $audit->log(AuditAction::Update, null, 'Site settings updated');

        return back()->with('status', 'Settings saved.');
    }
}
