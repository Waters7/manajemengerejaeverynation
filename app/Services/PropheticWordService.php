<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\PropheticWord;
use App\Models\User;
use App\Notifications\TeamAlert;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Prophetic word recordings uploaded by church staff to a person's account (private storage).
 */
class PropheticWordService
{
    public const DISK = 'local';

    public function __construct(private TeamNotifier $notifier) {}

    /**
     * @param  array{title: string, given_on?: ?string, given_by?: ?string, notes?: ?string}  $data
     */
    public function store(Profile $profile, UploadedFile $audio, array $data, User $by): PropheticWord
    {
        $path = $audio->storeAs("prophetic-words/{$profile->id}", Str::ulid().'.'.($audio->guessExtension() ?: 'mp3'), self::DISK);

        $word = $profile->propheticWords()->create([
            'title' => $data['title'],
            'given_on' => $data['given_on'] ?? null,
            'given_by' => $data['given_by'] ?? null,
            'notes' => $data['notes'] ?? null,
            'audio_path' => $path,
            'audio_name' => $audio->getClientOriginalName(),
            'mime_type' => $audio->getMimeType(),
            'size' => $audio->getSize(),
            'uploaded_by' => $by->id,
        ]);

        $this->notifier->toUser($profile->user, new TeamAlert(
            'prophetic_word',
            'A prophetic word was added to your account',
            "“{$word->title}” is ready to listen to.",
            route('member.prophetic-words'),
        ));

        return $word;
    }

    public function delete(PropheticWord $word): void
    {
        Storage::disk(self::DISK)->delete($word->audio_path);
        $word->delete();
    }
}
