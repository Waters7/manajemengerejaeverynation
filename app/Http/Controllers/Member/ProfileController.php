<?php

namespace App\Http\Controllers\Member;

use App\Enums\Gender;
use App\Enums\LifeStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdateProfileRequest;
use App\Services\MediaService;
use App\Services\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('member.profile', [
            'user' => $request->user(),
            'profile' => $request->user()->ensureProfile(),
            'genders' => Gender::options(),
            'lifeStages' => LifeStage::options(),
        ]);
    }

    public function update(UpdateProfileRequest $request, MediaService $media): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->ensureProfile();
        $data = $request->validated();

        DB::transaction(function () use ($user, $profile, $data, $request, $media) {
            $user->update([
                'name' => $data['name'],
                'nickname' => $data['nickname'],
                'email' => $data['email'],
                'whatsapp' => WhatsApp::normalize($data['whatsapp']),
            ]);

            $profile->update([
                'full_name' => $data['name'],
                'nickname' => $data['nickname'],
                'email' => $data['email'],
                'whatsapp' => WhatsApp::normalize($data['whatsapp']),
                'gender' => $data['gender'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'address' => $data['address'] ?? null,
                'area' => $data['area'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'company' => $data['company'] ?? null,
                'school_name' => $data['school_name'] ?? null,
                'life_stage' => $data['life_stage'] ?? null,
                'photo_path' => $media->replace($profile->photo_path, $request->file('photo'), 'profiles', 800),
            ]);
        });

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return back()->with('status', 'Password berhasil diubah.');
    }
}
