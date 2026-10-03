{{-- Draft / Scheduled / Published / Archived + publish date. Expects $model and $statuses. --}}
<div x-data="{ status: @js(old('status', $model->status?->value ?? 'draft')) }" class="space-y-4">
    <x-form.select name="status" label="Status" :options="$statuses" :value="$model->status" x-model="status" required />
    <div x-show="status === 'scheduled' || status === 'published'" x-cloak>
        <x-form.input name="published_at" type="datetime-local" label="Publish date" :value="$model->published_at" hint="Scheduled content goes live automatically at this time." />
    </div>
</div>
