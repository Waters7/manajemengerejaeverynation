<?php

namespace App\Http\Requests\Member;

use App\Enums\Gender;
use App\Enums\LifeStage;
use App\Rules\IndonesianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'nickname' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'whatsapp' => ['required', 'string', new IndonesianPhone],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'area' => ['nullable', 'string', 'max:120'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'school_name' => ['nullable', 'string', 'max:150'],
            'life_stage' => ['nullable', Rule::enum(LifeStage::class)],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
