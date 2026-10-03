<?php

namespace App\Http\Requests\Admin;

use App\Enums\DiscoverySource;
use App\Enums\Gender;
use App\Enums\LifeStage;
use App\Enums\MemberStatus;
use App\Models\Profile;
use App\Rules\IndonesianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->route('profile');

        return $profile ? $this->user()->can('update', $profile) : $this->user()->can('create', Profile::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'nickname' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'whatsapp' => ['nullable', 'string', new IndonesianPhone],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:500'],
            'area' => ['nullable', 'string', 'max:120'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'campus_id' => ['nullable', Rule::exists('campuses', 'id')],
            'school_name' => ['nullable', 'string', 'max:150'],
            'life_stage' => ['nullable', Rule::enum(LifeStage::class)],
            'join_date' => ['nullable', 'date'],
            'first_visit_date' => ['nullable', 'date'],
            'source' => ['nullable', Rule::enum(DiscoverySource::class)],
            'member_status' => ['required', Rule::enum(MemberStatus::class)],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
