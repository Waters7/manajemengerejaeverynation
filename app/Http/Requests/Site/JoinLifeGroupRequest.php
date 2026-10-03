<?php

namespace App\Http\Requests\Site;

use App\Rules\IndonesianPhone;
use Illuminate\Foundation\Http\FormRequest;

class JoinLifeGroupRequest extends FormRequest
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
            'age' => ['nullable', 'integer', 'between:10,100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nama', 'whatsapp' => 'nomor WhatsApp', 'age' => 'usia'];
    }
}
