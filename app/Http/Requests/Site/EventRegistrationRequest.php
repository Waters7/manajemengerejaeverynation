<?php

namespace App\Http\Requests\Site;

use App\Rules\IndonesianPhone;
use Illuminate\Foundation\Http\FormRequest;

class EventRegistrationRequest extends FormRequest
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
            'website' => ['prohibited'],
        ];
    }
}
