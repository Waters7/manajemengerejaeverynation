<?php

namespace App\Http\Requests\Admin;

use App\Enums\LifeGroupCategory;
use App\Enums\Weekday;
use App\Models\LifeGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LifeGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->route('lifeGroup');

        return $group ? $this->user()->can('update', $group) : $this->user()->can('create', LifeGroup::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::enum(LifeGroupCategory::class)],
            'area' => ['nullable', 'string', 'max:120'],
            'meeting_day' => ['nullable', Rule::enum(Weekday::class)],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:3000'],
            'leader_profile_id' => ['nullable', Rule::exists('profiles', 'id')],
            'co_leader_profile_id' => ['nullable', 'different:leader_profile_id', Rule::exists('profiles', 'id')],
            'campus_id' => ['nullable', Rule::exists('campuses', 'id')],
            'parent_id' => ['nullable', Rule::exists('life_groups', 'id')],
            'accepting_members' => ['boolean'],
            'is_public' => ['boolean'],
            'whatsapp_invite_url' => ['nullable', 'url', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'status' => ['required', Rule::in(['active', 'inactive', 'multiplied'])],
            'launched_at' => ['nullable', 'date'],
            'cover' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
