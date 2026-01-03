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
     * Check if URL should be handled by yt-dlp (video platform or non-direct-file URL)
     * If URL doesn't look like a direct file download, assume it's a video platform
     */
    public static function isVideoUrl(string $url): bool
    {
        // Known video platform domains - always use yt-dlp
        $videoPlatforms = [
            'youtube.com',
            'youtu.be',
            'vimeo.com',
            'dailymotion.com',
            'twitch.tv',
            'clips.twitch.tv',
            'twitter.com',
            'x.com',
            'facebook.com',
            'instagram.com',
            'tiktok.com',
            'bilibili.com',
            'nicovideo.jp',
            'reddit.com',
            'streamable.com',
            'v.redd.it',
        ];

        $lowercaseUrl = strtolower($url);

        // Check if it's a known video platform
        foreach ($videoPlatforms as $platform) {
            if (str_contains($lowercaseUrl, $platform)) {
                return true;
            }
        }

        // Direct file extensions - use aria2c for these
        $directFileExtensions = [
            '.zip',
            '.rar',
            '.7z',
            '.tar',
            '.gz',
            '.bz2',
            '.xz',
            '.iso',
            '.dmg',
            '.exe',
            '.msi',
            '.deb',
            '.rpm',
            '.apk',
            '.pdf',
            '.doc',
            '.docx',
            '.xls',
            '.xlsx',
            '.ppt',
            '.pptx',
            '.mp3',
            '.wav',
            '.flac',
            '.aac',
            '.ogg',
            '.wma',
            '.mp4',
            '.mkv',
            '.avi',
            '.mov',
            '.wmv',
            '.flv',
            '.webm',
            '.m4v',
            '.jpg',
            '.jpeg',
            '.png',
            '.gif',
            '.bmp',
            '.svg',
            '.webp',
            '.txt',
            '.csv',
            '.json',
            '.xml',
        ];

        // Parse URL to get the path
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';

        // Check if URL ends with a direct file extension
        foreach ($directFileExtensions as $ext) {
            if (str_ends_with(strtolower($path), $ext)) {
                return false; // Use aria2c for direct files
            }
        }

        // For URLs that:
        // 1. Have query parameters (like ?viewkey=xxx, ?v=xxx)
        // 2. End with paths like /watch, /video/, /view_video.php, etc.
        // Use yt-dlp as these are likely video pages
        if (!empty($parsedUrl['query'])) {
            return true; // Has query params, likely a video page
        }

        // Check for common video page patterns in path
        $videoPathPatterns = [
            '/watch',
            '/video',
            '/embed',
            '/player',
            '/clip',
            '/live',
            '/reel',
            '/shorts',
            '/status',
            '/post',
            '.php'
        ];

        foreach ($videoPathPatterns as $pattern) {
            if (str_contains($lowercaseUrl, $pattern)) {
                return true;
            }
        }

        // Default: if unsure and no file extension, try yt-dlp
        // yt-dlp will fail gracefully if it can't handle the URL
        return !str_contains($path, '.');
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

        // Universal cookies file - supports all platforms
        // Export cookies from browser using extension like "Get cookies.txt LOCALLY"
        // Combine all cookies from different sites into this single file
        $cookiesPath = storage_path('app/cookies.txt');
        $cookiesFlag = '';

        if (file_exists($cookiesPath) && filesize($cookiesPath) > 0) {
            $cookiesFlag = '--cookies ' . escapeshellarg($cookiesPath) . ' ';
            Log::info("yt-dlp: Using cookies from {$cookiesPath}");
        }

        return sprintf(
            'yt-dlp %s ' .
            '--format %s ' .
            '--merge-output-format mp4 ' .
            '--no-playlist ' .
            '--newline ' .
            '--progress ' .
            '--remote-components ejs:github ' .
            '%s' .
            '--output %s/%s ' .
            '--no-mtime ' .
            '--restrict-filenames ' .
            '2>&1',
            $escapedUrl,
            escapeshellarg($this->format),
            $cookiesFlag,
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
