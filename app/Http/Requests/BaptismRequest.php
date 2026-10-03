<?php

namespace App\Http\Requests;

use App\Enums\BaptismStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Water baptism status. Authorization (`disciple` ability on the person) happens in the controller.
 */
class BaptismRequest extends FormRequest
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
            'baptism_status' => ['required', Rule::enum(BaptismStatus::class)],
            'baptism_date' => [
                'nullable',
                'date',
                Rule::when($this->input('baptism_status') === BaptismStatus::Baptized->value, ['before_or_equal:today']),
                Rule::when($this->input('baptism_status') === BaptismStatus::Scheduled->value, ['required', 'after_or_equal:today']),
            ],
            'baptism_place' => ['nullable', 'string', 'max:190'],
            'baptism_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['baptism_status' => 'status baptisan', 'baptism_date' => 'tanggal baptisan', 'baptism_place' => 'tempat baptisan'];
    }
}
