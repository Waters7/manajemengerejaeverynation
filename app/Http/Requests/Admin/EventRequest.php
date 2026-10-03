<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event ? $this->user()->can('update', $event) : $this->user()->can('events.manage');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'event_category_id' => ['nullable', Rule::exists('event_categories', 'id')],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:20000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:190'],
            'maps_url' => ['nullable', 'url', 'max:500'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'registration_enabled' => ['boolean'],
            'registration_deadline' => ['nullable', 'date', 'before_or_equal:starts_at'],
            'waiting_list_enabled' => ['boolean'],
            'contact_person' => ['nullable', 'string', 'max:120'],
            'contact_whatsapp' => ['nullable', 'string', 'max:20'],
            'campus_id' => ['nullable', Rule::exists('campuses', 'id')],
            'life_group_id' => ['nullable', Rule::exists('life_groups', 'id')],
            'is_featured' => ['boolean'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'published_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'cover' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
