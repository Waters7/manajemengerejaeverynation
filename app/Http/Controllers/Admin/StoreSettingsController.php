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
 * STORE → Settings: payment details, pickup, delivery fee and the customer WhatsApp template.
 */
class StoreSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.store.settings', [
            'definitions' => Settings::definitions()['store'],
            'stored' => SiteSetting::pluck('value', 'key'),
        ]);
    }

    public function update(Request $request, Settings $settings, AuditLogger $audit): RedirectResponse
    {
        $definitions = collect(Settings::definitions()['store']);
        $data = $request->validate($definitions->map(fn ($definition) => match ($definition['type'] ?? 'text') {
            'toggle' => ['nullable', 'boolean'],
            'number' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            default => ['nullable', 'string', 'max:5000'],
        })->all());

        $settings->save($definitions->map(fn ($definition, $key) => match ($definition['type'] ?? 'text') {
            'toggle' => $request->boolean($key) ? '1' : '0',
            default => isset($data[$key]) ? (string) $data[$key] : null,
        })->all());
        $audit->log(AuditAction::Update, null, 'Store settings updated');

        return back()->with('status', 'Store settings saved.');
    }
}
