<?php

namespace App\Http\Requests\Site;

use App\Enums\Availability;
use App\Enums\DiscoverySource;
use App\Enums\Gender;
use App\Enums\LifeStage;
use App\Rules\IndonesianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetInvolvedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'nickname' => ['required', 'string', 'max:50'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_date' => ['nullable', 'date', 'before:today', 'after:1920-01-01'],
            'whatsapp' => ['required', 'string', new IndonesianPhone],
            'email' => ['nullable', 'email', 'max:190'],
            'area' => ['nullable', 'string', 'max:120'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'campus_name' => ['nullable', 'string', 'max:150'],
            'campus_id' => ['nullable', 'integer', Rule::exists('campuses', 'id')->where('is_active', true)],
            'life_stage' => ['nullable', Rule::enum(LifeStage::class)],
            'source' => ['nullable', Rule::enum(DiscoverySource::class)],
            'source_other' => ['nullable', 'required_if:source,other', 'string', 'max:150'],
            'interests' => ['required', 'array', 'min:1'],
            'interests.*' => ['integer', Rule::exists('involvement_interests', 'id')->where('is_active', true)],
            'ministries' => ['nullable', 'array'],
            'ministries.*' => ['integer', Rule::exists('ministries', 'id')->where('is_active', true)],
            'experience' => ['nullable', 'string', 'max:2000'],
            'skills' => ['nullable', 'array', 'max:15'],
            'skills.*' => ['string', 'max:50'],
            'availability' => ['nullable', 'array'],
            'availability.*' => [Rule::enum(Availability::class)],
            'motivation' => ['nullable', 'string', 'max:2000'],
            'prayer_request' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'nama lengkap',
            'nickname' => 'nama panggilan',
            'whatsapp' => 'nomor WhatsApp',
            'birth_date' => 'tanggal lahir',
            'interests' => 'pilihan "Saya tertarik untuk"',
            'consent' => 'persetujuan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['interests.required' => 'Pilih minimal satu hal yang kamu minati.'];
    }
}
