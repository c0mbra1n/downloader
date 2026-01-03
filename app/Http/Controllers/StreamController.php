<?php

namespace App\Http\Controllers;

use App\Models\Download;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamController extends Controller
{
    /**
     * Stream media file with Range Request support
     */
    public function stream(Request $request, Download $download): Response
    {
        $path = $download->absolute_path;

        if (!$path || !file_exists($path)) {
            abort(404, 'File not found');
        }

        $fileSize = filesize($path);
        $mimeType = $download->mime_type ?? mime_content_type($path);

        // Handle Range Request for video/audio seeking
        $range = $request->header('Range');

        if ($range) {
            return $this->streamRange($path, $fileSize, $mimeType, $range, $download->filename);
        }

        // Full file response
        return $this->streamFull($path, $fileSize, $mimeType, $download->filename);
    }

    /**
     * Stream full file
     */
    protected function streamFull(string $path, int $fileSize, string $mimeType, string $filename): StreamedResponse
    {
        return response()->stream(function () use ($path) {
            $handle = fopen($path, 'rb');
            while (!feof($handle)) {
                echo fread($handle, 1024 * 1024); // 1MB chunks
                flush();
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    /**
     * Stream partial content (206 response)
     */
    protected function streamRange(
        string $path,
        int $fileSize,
        string $mimeType,
        string $range,
        string $filename
    ): StreamedResponse {
        // Parse range header
        preg_match('/bytes=(\d*)-(\d*)/', $range, $matches);

        $start = $matches[1] !== '' ? (int) $matches[1] : 0;
        $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

        // Validate range
        if ($start > $end || $start >= $fileSize) {
            return response()->stream(function () {}, 416, [
                'Content-Range' => "bytes */{$fileSize}",
            ]);
        }

        $end = min($end, $fileSize - 1);
        $length = $end - $start + 1;

        return response()->stream(function () use ($path, $start, $length) {
            $handle = fopen($path, 'rb');
            fseek($handle, $start);

            $remaining = $length;
            while ($remaining > 0 && !feof($handle)) {
                $chunkSize = min(1024 * 1024, $remaining); // 1MB chunks
                echo fread($handle, $chunkSize);
                $remaining -= $chunkSize;
                flush();
            }

            fclose($handle);
        }, 206, [
            'Content-Type' => $mimeType,
            'Content-Length' => $length,
            'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
