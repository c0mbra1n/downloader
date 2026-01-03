<?php

namespace App\Services;

use App\Enums\DownloadStatus;
use App\Models\Download;
use Illuminate\Support\Facades\Log;

class YtDlpService
{
    protected string $basePath;
    protected string $format;

    public function __construct()
    {
        $this->basePath = storage_path('app/downloads');
        $this->format = config('download.ytdlp_format', 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best');
    }

    /**
     * Check if URL is a video platform URL
     */
    public static function isVideoUrl(string $url): bool
    {
        $videoPatterns = [
            'youtube.com',
            'youtu.be',
            'vimeo.com',
            'dailymotion.com',
            'twitch.tv',
            'twitter.com',
            'x.com',
            'facebook.com',
            'instagram.com',
            'tiktok.com',
            'bilibili.com',
            'nicovideo.jp',
            'pornhub.com',
            'xvideos.com',
            'xnxx.com',
            'redtube.com',
            'youporn.com',
            'spankbang.com',
            'xhamster.com',
            'eporner.com',
            'tube8.com',
            'youjizz.com',
            'reddit.com',
            'streamable.com',
            'v.redd.it',
            'clips.twitch.tv',
        ];

        foreach ($videoPatterns as $pattern) {
            if (str_contains(strtolower($url), $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Download video using yt-dlp
     */
    public function download(Download $download): bool
    {
        $url = $download->url;
        $videoDir = $this->basePath . '/videos';

        // Ensure video directory exists
        if (!is_dir($videoDir)) {
            mkdir($videoDir, 0755, true);
        }

        // Update status to downloading
        $download->update([
            'status' => DownloadStatus::DOWNLOADING,
            'started_at' => now(),
        ]);

        // Build yt-dlp command
        $command = $this->buildCommand($url, $videoDir, $download->id);

        Log::info("Starting yt-dlp download for ID {$download->id}: {$url}");
        Log::debug("yt-dlp command: {$command}");

        // Execute yt-dlp with progress monitoring
        $result = $this->executeWithProgress($command, $download);

        if (!$result['success']) {
            $download->update([
                'status' => DownloadStatus::FAILED,
                'error_message' => $result['error'] ?? 'Download failed',
            ]);
            return false;
        }

        // Find the downloaded file
        $downloadedFile = $this->findDownloadedFile($videoDir, $download->id);

        if (!$downloadedFile) {
            $download->update([
                'status' => DownloadStatus::FAILED,
                'error_message' => 'Downloaded file not found',
            ]);
            return false;
        }

        // Get file info
        $mimeType = mime_content_type($downloadedFile) ?: 'video/mp4';
        $fileSize = filesize($downloadedFile);
        $filename = basename($downloadedFile);

        // Update download record
        $download->update([
            'status' => DownloadStatus::COMPLETED,
            'filename' => $filename,
            'file_path' => 'downloads/videos/' . $filename,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'progress' => 100,
            'downloaded_bytes' => $fileSize,
            'total_bytes' => $fileSize,
            'completed_at' => now(),
        ]);

        Log::info("yt-dlp download completed for ID {$download->id}: {$filename}");

        return true;
    }

    /**
     * Build yt-dlp command
     */
    protected function buildCommand(string $url, string $dir, int $downloadId): string
    {
        $escapedUrl = escapeshellarg($url);
        $escapedDir = escapeshellarg($dir);
        $outputTemplate = escapeshellarg("%(title).100s__{$downloadId}.%(ext)s");

        return sprintf(
            'yt-dlp %s ' .
            '--format %s ' .
            '--merge-output-format mp4 ' .
            '--no-playlist ' .
            '--newline ' .
            '--progress ' .
            '--remote-components ejs:github ' .
            '--output %s/%s ' .
            '--no-mtime ' .
            '--restrict-filenames ' .
            '2>&1',
            $escapedUrl,
            escapeshellarg($this->format),
            $escapedDir,
            $outputTemplate
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
            return ['success' => false, 'error' => 'Failed to start yt-dlp process'];
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

            // Read from stdout
            $line = fgets($pipes[1]);
            if ($line !== false) {
                $output .= $line;
                $progressInfo = $this->parseProgress($line);

                if ($progressInfo && $progressInfo['percent'] > $lastProgress) {
                    $lastProgress = $progressInfo['percent'];

                    if ((time() - $lastUpdate) >= $updateInterval) {
                        // Use DB facade directly to ensure immediate update
                        \DB::table('downloads')
                            ->where('id', $download->id)
                            ->update(['progress' => $lastProgress, 'updated_at' => now()]);
                        $lastUpdate = time();
                        Log::debug("Progress updated: {$lastProgress}%");
                    }
                }
            }

            // Also read from stderr (yt-dlp often writes progress there)
            $errLine = fgets($pipes[2]);
            if ($errLine !== false) {
                $output .= $errLine;
                $progressInfo = $this->parseProgress($errLine);

                if ($progressInfo && $progressInfo['percent'] > $lastProgress) {
                    $lastProgress = $progressInfo['percent'];

                    if ((time() - $lastUpdate) >= $updateInterval) {
                        \DB::table('downloads')
                            ->where('id', $download->id)
                            ->update(['progress' => $lastProgress, 'updated_at' => now()]);
                        $lastUpdate = time();
                        Log::debug("Progress updated: {$lastProgress}%");
                    }
                }
            }

            usleep(50000); // 50ms sleep for more responsive updates
        }

        // Read remaining output
        $output .= stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            Log::error("yt-dlp failed with exit code {$exitCode}: {$errors}");
            return [
                'success' => false,
                'error' => "yt-dlp exited with code {$exitCode}: " . trim($errors ?: $output),
            ];
        }

        return ['success' => true, 'output' => $output];
    }

    /**
     * Parse progress from yt-dlp output
     */
    protected function parseProgress(string $line): ?array
    {
        // yt-dlp outputs progress like: [download]  45.2% of 100.00MiB
        if (preg_match('/\[download\]\s+(\d+(?:\.\d+)?)%/', $line, $matches)) {
            return ['percent' => (int) round((float) $matches[1])];
        }

        return null;
    }

    /**
     * Find downloaded file
     */
    protected function findDownloadedFile(string $dir, int $downloadId): ?string
    {
        // Look for files with our download ID marker
        $pattern = $dir . '/*__' . $downloadId . '.*';
        $files = glob($pattern);

        if (!empty($files)) {
            // Return the most recent file
            usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
            return $files[0];
        }

        return null;
    }
}
