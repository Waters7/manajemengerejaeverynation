<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\PropheticWord;
use App\Services\PropheticWordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Church staff upload prophetic word recordings to a person's account.
 */
class PropheticWordController extends Controller
{
    public function store(Request $request, Profile $profile, PropheticWordService $words): RedirectResponse
    {
        $this->authorize('managePropheticWords', $profile);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'given_on' => ['nullable', 'date', 'before_or_equal:today'],
            'given_by' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'audio' => ['required', 'file', 'mimes:mp3,m4a,mp4,aac,wav,ogg,oga,webm', 'max:20480'],
        ]);

        $words->store($profile, $request->file('audio'), collect($data)->except('audio')->all(), $request->user());

        return redirect()->to(route('admin.members.show', $profile).'#files')
            ->with('status', 'Prophetic word uploaded to '.$profile->displayName()."'s account.");
    }

    public function destroy(PropheticWord $word, PropheticWordService $words): RedirectResponse
    {
        $this->authorize('delete', $word);
        $profile = $word->profile;
        $words->delete($word);

        return redirect()->to(route('admin.members.show', $profile).'#files')->with('status', 'Recording removed.');
    }
}
