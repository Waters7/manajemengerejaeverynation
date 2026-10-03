<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiscipleshipMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'met_on' => ['required', 'date', 'before_or_equal:today'],
            'topic' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'next_follow_up_at' => ['nullable', 'date', 'after_or_equal:met_on'],
        ];
    }
}
