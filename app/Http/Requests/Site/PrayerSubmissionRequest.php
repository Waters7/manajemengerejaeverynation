<?php

namespace App\Http\Requests\Site;

use App\Enums\PrayerVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrayerSubmissionRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:120'],
            'contact' => ['nullable', 'string', 'max:120'],
            'request' => ['required', 'string', 'min:5', 'max:3000'],
            'visibility' => ['required', Rule::enum(PrayerVisibility::class)],
            'is_anonymous' => ['nullable', 'boolean'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['request' => 'pokok doa', 'visibility' => 'siapa yang boleh membaca'];
    }
}
