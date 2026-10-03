<?php

namespace App\Http\Requests\Site;

use App\Enums\Availability;
use App\Rules\IndonesianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'whatsapp' => ['required', 'string', new IndonesianPhone],
            'email' => ['nullable', 'email', 'max:190'],
            'area' => ['nullable', 'string', 'max:120'],
            'church_connection' => ['nullable', 'string', 'max:190'],
            'ministries' => ['required', 'array', 'min:1'],
            'ministries.*' => ['integer', Rule::exists('ministries', 'id')->where('is_active', true)],
            'skills' => ['nullable', 'array', 'max:15'],
            'skills.*' => ['string', 'max:50'],
            'experience' => ['nullable', 'string', 'max:2000'],
            'availability' => ['nullable', 'array'],
            'availability.*' => [Rule::enum(Availability::class)],
            'motivation' => ['required', 'string', 'max:2000'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nama', 'whatsapp' => 'nomor WhatsApp', 'ministries' => 'ministry', 'motivation' => 'alasan melayani'];
    }
}
