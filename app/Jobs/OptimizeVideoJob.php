<?php

namespace App\Jobs;

use App\Enums\DownloadStatus;
use App\Models\Download;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class OptimizeVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 3600; // 1 hour

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Download $download
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $inputPath = $this->download->absolute_path;

        if (!$inputPath || !file_exists($inputPath)) {
            Log::error("OptimizeVideoJob: File not found at {$inputPath}");
            $this->download->update(['status' => DownloadStatus::FAILED, 'error_message' => 'File to optimize not found']);
            return;
        }

        $outputPath = $inputPath . '.optimized.mp4';

        Log::info("OptimizeVideoJob: Starting optimization for {$this->download->filename} (ID: {$this->download->id})");

        // Command details:
        // -y: overwrite
        // -c:v libx264: ensure H.264
        // -preset fast: good balance for speed/quality
        // -crf 23: standard quality
        // -c:a aac: ensure AAC audio
        // -movflags +faststart: move moov atom to start for web playback
        $command = [
            'ffmpeg',
            '-y',
            '-i',
            $inputPath,
            '-c:v',
            'libx264',
            '-preset',
            'fast',
            '-crf',
            '23',
            '-c:a',
            'aac',
            '-b:a',
            '128k',
            '-movflags',
            '+faststart',
            $outputPath
        ];

        try {
            Log::debug("OptimizeVideoJob running: " . implode(' ', $command));
            $result = Process::timeout($this->timeout)->run($command);

            if ($result->successful()) {
                // Replace original file with optimized one
                unlink($inputPath);
                rename($outputPath, $inputPath);

                // Update file size and status
                $newSize = filesize($inputPath);
                $this->download->update([
                    'status' => DownloadStatus::COMPLETED,
                    'file_size' => $newSize,
                    'downloaded_bytes' => $newSize,
                    'total_bytes' => $newSize,
                    'completed_at' => now(), // refresh completed_at to now
                ]);

                Log::info("OptimizeVideoJob: Optimization successful for {$this->download->filename}");
            } else {
                Log::error("OptimizeVideoJob: Optimization failed for {$this->download->filename}");
                Log::error($result->errorOutput());

                // Cleanup temp file if it exists
                if (file_exists($outputPath)) {
                    @unlink($outputPath);
                }

                // Fallback: mark as completed but log failure so it's not stuck
                $this->download->update([
                    'status' => DownloadStatus::COMPLETED,
                    'error_message' => 'Optimization failed, kept original: ' . trim($result->errorOutput()),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("OptimizeVideoJob exception for ID {$this->download->id}: " . $e->getMessage());

            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }

            $this->download->update([
                'status' => DownloadStatus::COMPLETED,
                'error_message' => 'Optimization exception, kept original: ' . $e->getMessage(),
            ]);
        }
    }
}
