<?php

namespace App\Http\Controllers;

use App\Models\PropheticWord;
use App\Services\PropheticWordService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Streams a prophetic word recording (supports range requests so the player can seek).
 */
class PropheticWordController extends Controller
{
    public function audio(PropheticWord $word): BinaryFileResponse
    {
        $this->authorize('view', $word);
        abort_unless(Storage::disk(PropheticWordService::DISK)->exists($word->audio_path), 404);

        return response()->file(Storage::disk(PropheticWordService::DISK)->path($word->audio_path), [
            'Content-Type' => $word->mime_type ?: 'audio/mpeg',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    public function download(PropheticWord $word): BinaryFileResponse
    {
        $this->authorize('view', $word);
        abort_unless(Storage::disk(PropheticWordService::DISK)->exists($word->audio_path), 404);

        return response()->download(
            Storage::disk(PropheticWordService::DISK)->path($word->audio_path),
            $word->audio_name ?: str($word->title)->slug().'.mp3',
        );
    }
}
