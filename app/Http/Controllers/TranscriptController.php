<?php

namespace App\Http\Controllers;

use App\Managers\TranscriptManager;
use App\Repositories\VideoRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TranscriptController extends Controller
{
    public function __construct(
        protected TranscriptManager $transcriptManager,
        protected VideoRepository $videoRepository
    ) {}

    public function show(int $videoId): JsonResponse
    {
        $video = $this->videoRepository->findOrFail($videoId);

        if ($video->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $transcript = $this->transcriptManager->getTranscriptByVideoId($videoId);

        if (! $transcript) {
            return response()->json(['error' => 'Transcript not found'], 404);
        }

        return response()->json($transcript);
    }

    public function retry(int $videoId): JsonResponse
    {
        $video = $this->videoRepository->findOrFail($videoId);

        if ($video->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $transcript = $this->transcriptManager->getTranscriptByVideoId($videoId);

        if (! $transcript) {
            return response()->json(['error' => 'Transcript not found'], 404);
        }

        $success = $this->transcriptManager->retryTranscription($transcript->id);

        if (! $success) {
            return response()->json([
                'error' => 'Cannot retry transcription while it is processing',
            ], 400);
        }

        return response()->json([
            'message' => 'Transcription retry started',
        ]);
    }
}
