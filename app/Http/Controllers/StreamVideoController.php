<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StreamVideoController extends Controller
{
    /**
     * Start a new streaming upload session.
     * Returns a session ID that will be used to upload chunks.
     */
    public function startUpload(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'mime_type' => 'nullable|string',
        ]);

        // Generate a unique session ID
        $sessionId = Str::uuid()->toString();

        // Create temp directory for chunks
        $chunkDir = storage_path("app/temp/stream-uploads/{$sessionId}");
        if (!file_exists($chunkDir)) {
            mkdir($chunkDir, 0755, true);
        }

        // Store session metadata
        $metadata = [
            'user_id' => Auth::id() ?? 1, // Default to user 1 for MVP
            'title' => $request->title,
            'mime_type' => $request->mime_type ?? 'video/webm',
            'started_at' => now()->toISOString(),
            'chunks' => [],
            'total_size' => 0,
        ];

        file_put_contents("{$chunkDir}/metadata.json", json_encode($metadata));

        return response()->json([
            'session_id' => $sessionId,
            'message' => 'Upload session started',
        ]);
    }

    /**
     * Receive a video chunk during recording.
     * Chunks are saved to disk and will be merged when recording completes.
     */
    public function uploadChunk(Request $request, $sessionId)
    {
        $request->validate([
            'chunk' => 'required|file',
            'chunk_index' => 'required|integer|min:0',
        ]);

        $chunkDir = storage_path("app/temp/stream-uploads/{$sessionId}");

        // Verify session exists
        if (!file_exists("{$chunkDir}/metadata.json")) {
            return response()->json(['message' => 'Invalid session'], 404);
        }

        // Read metadata
        $metadata = json_decode(file_get_contents("{$chunkDir}/metadata.json"), true);

        // Verify user owns this session (skip check for MVP mode)
        $userId = Auth::id() ?? 1;
        if ($metadata['user_id'] !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Save chunk
        $chunkIndex = $request->chunk_index;
        $chunkFile = $request->file('chunk');
        $chunkPath = "{$chunkDir}/chunk_{$chunkIndex}.webm";

        $chunkFile->move($chunkDir, "chunk_{$chunkIndex}.webm");

        // Update metadata
        $chunkSize = filesize($chunkPath);
        $metadata['chunks'][$chunkIndex] = [
            'index' => $chunkIndex,
            'size' => $chunkSize,
            'received_at' => now()->toISOString(),
        ];
        $metadata['total_size'] += $chunkSize;
        $metadata['last_chunk_at'] = now()->toISOString();

        file_put_contents("{$chunkDir}/metadata.json", json_encode($metadata));

        return response()->json([
            'message' => 'Chunk received',
            'chunk_index' => $chunkIndex,
            'chunk_size' => $chunkSize,
            'total_size' => $metadata['total_size'],
            'chunks_received' => count($metadata['chunks']),
        ]);
    }

    /**
     * Complete the streaming upload.
     * Merges all chunks into a single video file and creates the Video record.
     */
    public function completeUpload(Request $request, $sessionId)
    {
        $request->validate([
            'duration' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
        ]);

        $chunkDir = storage_path("app/temp/stream-uploads/{$sessionId}");

        // Verify session exists
        if (!file_exists("{$chunkDir}/metadata.json")) {
            return response()->json(['message' => 'Invalid session'], 404);
        }

        // Read metadata
        $metadata = json_decode(file_get_contents("{$chunkDir}/metadata.json"), true);

        // Verify user owns this session (skip check for MVP mode)
        $userId = Auth::id() ?? 1;
        if ($metadata['user_id'] !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Sort chunks by index
        ksort($metadata['chunks']);

        // Merge chunks into single file
        $mergedPath = "{$chunkDir}/merged.webm";
        $mergedFile = fopen($mergedPath, 'wb');

        foreach ($metadata['chunks'] as $index => $chunkInfo) {
            $chunkPath = "{$chunkDir}/chunk_{$index}.webm";
            if (file_exists($chunkPath)) {
                $chunkData = file_get_contents($chunkPath);
                fwrite($mergedFile, $chunkData);
            }
        }

        fclose($mergedFile);

        // Create Video record
        $title = $request->title ?? $metadata['title'];
        $video = Video::create([
            'user_id' => $userId,
            'title' => $title,
            'description' => null,
            'duration' => $request->duration ?? 0,
            'is_public' => true,
        ]);

        // Add merged video to media library
        $video->addMedia($mergedPath)
            ->usingFileName("video_{$video->id}.webm")
            ->toMediaCollection('videos');

        // Generate thumbnail
        $video->generateThumbnailFromMidpoint();

        // Clean up chunks directory
        $this->cleanupSession($sessionId);

        return response()->json([
            'message' => 'Video uploaded successfully',
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'duration' => $video->duration,
                'url' => url("/api/share/video/{$video->share_token}/stream"),
                'thumbnail' => $video->getThumbnailUrl(),
                'share_url' => $video->getShareUrl(),
                'is_public' => $video->is_public,
                'created_at' => $video->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Cancel/abort an upload session.
     */
    public function cancelUpload(Request $request, $sessionId)
    {
        $chunkDir = storage_path("app/temp/stream-uploads/{$sessionId}");

        if (file_exists("{$chunkDir}/metadata.json")) {
            $metadata = json_decode(file_get_contents("{$chunkDir}/metadata.json"), true);

            // Verify user owns this session (skip check for MVP mode)
            $userId = Auth::id() ?? 1;
            if ($metadata['user_id'] !== $userId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $this->cleanupSession($sessionId);

        return response()->json([
            'message' => 'Upload cancelled',
        ]);
    }

    /**
     * Get upload session status.
     */
    public function getStatus($sessionId)
    {
        $chunkDir = storage_path("app/temp/stream-uploads/{$sessionId}");

        if (!file_exists("{$chunkDir}/metadata.json")) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $metadata = json_decode(file_get_contents("{$chunkDir}/metadata.json"), true);

        // Verify user owns this session (skip check for MVP mode)
        $userId = Auth::id() ?? 1;
        if ($metadata['user_id'] !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'session_id' => $sessionId,
            'title' => $metadata['title'],
            'chunks_received' => count($metadata['chunks']),
            'total_size' => $metadata['total_size'],
            'started_at' => $metadata['started_at'],
            'last_chunk_at' => $metadata['last_chunk_at'] ?? null,
        ]);
    }

    /**
     * Clean up a session's temporary files.
     */
    private function cleanupSession($sessionId)
    {
        $chunkDir = storage_path("app/temp/stream-uploads/{$sessionId}");

        if (file_exists($chunkDir)) {
            // Delete all files in directory
            $files = glob("{$chunkDir}/*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            // Remove directory
            rmdir($chunkDir);
        }
    }
}
