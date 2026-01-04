<?php

namespace App\Services;

use App\Enums\DownloadStatus;
use App\Models\Download;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Aria2Service
{
    protected int $maxConnections;
    protected int $splitCount;
    protected string $basePath;

    public function __construct()
    {
        $this->maxConnections = (int) config('download.max_connections', 16);
        $this->splitCount = (int) config('download.split_count', 16);
        $this->basePath = storage_path('app/downloads');
    }

    /**
     * Download a file using aria2c
     */
    public function download(Download $download): bool
    {
        $url = $download->url;
        $tempDir = $this->basePath . '/temp';

        // Ensure temp directory exists
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Generate temporary filename
        $tempFilename = 'download_' . $download->id . '_' . time();

        // Update status to downloading
        $download->update([
            'status' => DownloadStatus::DOWNLOADING,
            'started_at' => now(),
        ]);

        // Build aria2c command
        $command = $this->buildCommand($url, $tempDir, $tempFilename);

        Log::info("Starting download for ID {$download->id}: {$url}");
        Log::debug("Aria2c command: {$command}");

        // Execute aria2c with progress monitoring
        $result = $this->executeWithProgress($command, $download);

        if (!$result['success']) {
            $download->update([
                'status' => DownloadStatus::FAILED,
                'error_message' => $result['error'] ?? 'Download failed',
            ]);
            return false;
        }

        // Find the downloaded file
        $downloadedFile = $this->findDownloadedFile($tempDir, $tempFilename, $url);

        if (!$downloadedFile) {
            $download->update([
                'status' => DownloadStatus::FAILED,
                'error_message' => 'Downloaded file not found',
            ]);
            return false;
        }

        // Get file info
        $mimeType = mime_content_type($downloadedFile);
        $fileSize = filesize($downloadedFile);
        $originalFilename = $this->extractFilename($url, $downloadedFile);

        // Determine storage category
        $download->mime_type = $mimeType;
        $download->filename = $originalFilename; // Set this so getStorageCategory() can use the extension fallback
        $category = $download->getStorageCategory();

        // Ensure category directory exists
        $categoryDir = $this->basePath . '/' . $category;
        if (!is_dir($categoryDir)) {
            mkdir($categoryDir, 0755, true);
        }

        // Generate final filename (avoid conflicts)
        $finalFilename = $this->generateUniqueFilename($categoryDir, $originalFilename);
        $finalPath = $categoryDir . '/' . $finalFilename;

        // Move file to final location
        rename($downloadedFile, $finalPath);

        // Update download record
        $download->update([
            'status' => DownloadStatus::COMPLETED,
            'filename' => $finalFilename,
            'file_path' => 'downloads/' . $category . '/' . $finalFilename,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'progress' => 100,
            'downloaded_bytes' => $fileSize,
            'total_bytes' => $fileSize,
            'completed_at' => now(),
        ]);

        Log::info("Download completed for ID {$download->id}: {$finalFilename}");

        return true;
    }

    /**
     * Build aria2c command
     */
    protected function buildCommand(string $url, string $dir, string $filename): string
    {
        $escapedUrl = escapeshellarg($url);
        $escapedDir = escapeshellarg($dir);
        $escapedFilename = escapeshellarg($filename);

        // Universal cookies file - same as yt-dlp
        $cookiesPath = storage_path('app/cookies.txt');
        $cookiesFlag = '';
        if (file_exists($cookiesPath) && filesize($cookiesPath) > 0) {
            $cookiesFlag = '--load-cookies=' . escapeshellarg($cookiesPath) . ' ';
            Log::info("Aria2c: Using cookies from {$cookiesPath}");
        }

        return sprintf(
            'aria2c %s ' .
            '%s' .
            '--max-connection-per-server=%d ' .
            '--split=%d ' .
            '--min-split-size=1M ' .
            '--continue=true ' .
            '--auto-file-renaming=false ' .
            '--allow-overwrite=true ' .
            '--summary-interval=1 ' .
            '--console-log-level=notice ' .
            '--show-console-readout=true ' .
            '--human-readable=true ' .
            '--dir=%s ' .
            '--out=%s ' .
            '--user-agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36" ' .
            '2>&1',
            $escapedUrl,
            $cookiesFlag,
            $this->maxConnections,
            $this->splitCount,
            $escapedDir,
            $escapedFilename
        );
    }

    /**
     * Execute command with progress monitoring
     */
    protected function executeWithProgress(string $command, Download $download): array
    {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            return ['success' => false, 'error' => 'Failed to start aria2c process'];
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output = '';
        $lastUpdate = 0;
        $updateInterval = 1;
        $lastProgress = 0;

        while (true) {
            $status = proc_get_status($process);

            if (!$status['running']) {
                break;
            }

            $line = fgets($pipes[1]);
            if ($line !== false) {
                $output .= $line;

                // Parse progress from aria2c output
                $progressInfo = $this->parseProgress($line);

                if ($progressInfo && $progressInfo['percent'] > $lastProgress) {
                    $lastProgress = $progressInfo['percent'];

                    if ((time() - $lastUpdate) >= $updateInterval) {
                        // Use DB facade directly for immediate update
                        \DB::table('downloads')
                            ->where('id', $download->id)
                            ->update([
                                'progress' => $lastProgress,
                                'downloaded_bytes' => $progressInfo['downloaded'] ?? 0,
                                'total_bytes' => $progressInfo['total'] ?? 0,
                                'updated_at' => now(),
                            ]);
                        $lastUpdate = time();
                        Log::debug("Aria2c progress updated: {$lastProgress}%");
                    }
                }
            }

            usleep(50000); // 50ms sleep
        }

        // Read remaining output
        $output .= stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            Log::error("Aria2c failed with exit code {$exitCode}: {$errors}");
            return [
                'success' => false,
                'error' => "aria2c exited with code {$exitCode}: " . trim($errors ?: $output),
            ];
        }

        return ['success' => true, 'output' => $output];
    }

    /**
     * Parse progress from aria2c output
     */
    protected function parseProgress(string $line): ?array
    {
        // aria2c outputs progress like: [#abc123 1.2MiB/10MiB(12%) CN:16 DL:1.5MiB]
        if (preg_match('/\((\d+)%\)/', $line, $matches)) {
            $percent = (int) $matches[1];

            $downloaded = 0;
            $total = 0;

            // Try to parse sizes
            if (preg_match('/(\d+(?:\.\d+)?)(Ki?B|Mi?B|Gi?B)\/(\d+(?:\.\d+)?)(Ki?B|Mi?B|Gi?B)/', $line, $sizeMatches)) {
                $downloaded = $this->parseSize($sizeMatches[1], $sizeMatches[2]);
                $total = $this->parseSize($sizeMatches[3], $sizeMatches[4]);
            }

            return [
                'percent' => $percent,
                'downloaded' => $downloaded,
                'total' => $total,
            ];
        }

        return null;
    }

    /**
     * Parse size string to bytes
     */
    protected function parseSize(string $value, string $unit): int
    {
        $bytes = (float) $value;

        $unit = strtoupper($unit);
        if (str_contains($unit, 'G')) {
            $bytes *= 1073741824;
        } elseif (str_contains($unit, 'M')) {
            $bytes *= 1048576;
        } elseif (str_contains($unit, 'K')) {
            $bytes *= 1024;
        }

        return (int) $bytes;
    }

    /**
     * Find downloaded file in temp directory
     */
    protected function findDownloadedFile(string $dir, string $tempFilename, string $url): ?string
    {
        // First check exact temp filename
        $exactPath = $dir . '/' . $tempFilename;
        if (file_exists($exactPath)) {
            return $exactPath;
        }

        // Check for server-provided filename (aria2c might append extension)
        $files = glob($dir . '/' . $tempFilename . '*');
        if (!empty($files)) {
            return $files[0];
        }

        return null;
    }

    /**
     * Extract filename from URL or downloaded file
     */
    protected function extractFilename(string $url, string $filePath): string
    {
        // Try to get filename from URL
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        $urlFilename = basename($path);

        // Remove query strings and decode
        $urlFilename = preg_replace('/\?.*$/', '', $urlFilename);
        $urlFilename = urldecode($urlFilename);

        if ($urlFilename && $urlFilename !== '' && $urlFilename !== '/') {
            return $urlFilename;
        }

        // Fallback: use temp filename with detected extension
        $mime = mime_content_type($filePath);
        $extension = $this->mimeToExtension($mime);

        return 'download_' . time() . '.' . $extension;
    }

    /**
     * Generate unique filename in directory
     */
    protected function generateUniqueFilename(string $dir, string $filename): string
    {
        $path = $dir . '/' . $filename;

        if (!file_exists($path)) {
            return $filename;
        }

        $info = pathinfo($filename);
        $name = $info['filename'];
        $ext = $info['extension'] ?? '';

        $counter = 1;
        while (file_exists($dir . '/' . $name . '_' . $counter . ($ext ? '.' . $ext : ''))) {
            $counter++;
        }

        return $name . '_' . $counter . ($ext ? '.' . $ext : '');
    }

    /**
     * Convert MIME type to file extension
     */
    protected function mimeToExtension(string $mime): string
    {
        $map = [
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/x-matroska' => 'mkv',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'audio/mpeg' => 'mp3',
            'audio/mp4' => 'm4a',
            'audio/ogg' => 'ogg',
            'audio/wav' => 'wav',
            'audio/flac' => 'flac',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'application/x-rar-compressed' => 'rar',
            'application/x-7z-compressed' => '7z',
            'application/gzip' => 'gz',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'text/plain' => 'txt',
            'text/html' => 'html',
        ];

        return $map[$mime] ?? 'bin';
    }
}
