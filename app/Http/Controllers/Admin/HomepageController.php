<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\AuditLogger;
use App\Services\MediaService;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CONTENT → Homepage and Get Involved texts (stored as site settings).
 */
class HomepageController extends Controller
{
    private const GROUPS = ['homepage', 'get_involved'];

    public function edit(): View
    {
        return view('admin.homepage', [
            'groups' => collect(Settings::definitions())->only(self::GROUPS),
            'stored' => SiteSetting::pluck('value', 'key'),
        ]);
    }

    public function update(Request $request, Settings $settings, MediaService $media, AuditLogger $audit): RedirectResponse
    {
        $definitions = collect(Settings::definitions())->only(self::GROUPS)->collapse();

        $rules = $definitions->map(fn ($def) => ($def['type'] ?? 'text') === 'image'
            ? ['nullable', 'image', 'max:8192']
            : ['nullable', 'string', 'max:5000'])->all();
        $data = $request->validate($rules);

        $values = [];
        foreach ($definitions as $key => $definition) {
            if (($definition['type'] ?? 'text') === 'image') {
                if ($request->hasFile($key)) {
                    $values[$key] = $media->replace($settings->get($key) ?: null, $request->file($key), 'site', 2400);
                } elseif ($request->boolean("remove_{$key}")) {
                    $media->delete($settings->get($key) ?: null);
                    $values[$key] = null;
                }

                continue;
            }
            $values[$key] = $data[$key] ?? null;
        }

        $settings->save($values);
        $audit->log(AuditAction::Update, null, 'Homepage content updated');

        return back()->with('status', 'Homepage updated.');
    }
}
