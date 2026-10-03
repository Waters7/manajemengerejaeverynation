<?php

namespace App\Http\Requests\Site;

use App\Rules\IndonesianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConnectCardRequest extends FormRequest
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
            'nickname' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['required', 'string', new IndonesianPhone],
            'email' => ['nullable', 'email', 'max:190'],
            'birth_date' => ['nullable', 'date', 'before:today', 'after:1920-01-01'],
            'area' => ['nullable', 'string', 'max:120'],
            'interests' => ['nullable', 'array'],
            'interests.*' => ['integer', Rule::exists('involvement_interests', 'id')->where('is_active', true)],
            'prayer_request' => ['nullable', 'string', 'max:2000'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['full_name' => 'nama', 'whatsapp' => 'nomor WhatsApp'];
    }
}
