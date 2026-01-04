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

        // Get video duration for progress calculation
        $duration = 0;
        try {
            $durationResult = Process::run([
                'ffprobe',
                '-v',
                'error',
                '-show_entries',
                'format=duration',
                '-of',
                'default=noprint_wrappers=1:nokey=1',
                $inputPath
            ]);
            if ($durationResult->successful()) {
                $duration = (float) trim($durationResult->output());
                Log::debug("OptimizeVideoJob: Video duration detected: {$duration}s");
            }
        } catch (\Exception $e) {
            Log::warning("OptimizeVideoJob: Could not detect duration: " . $e->getMessage());
        }

        $outputPath = $inputPath . '.optimized.mp4';

        Log::info("OptimizeVideoJob: Starting optimization for {$this->download->filename} (ID: {$this->download->id})");

        $command = [
            'ffmpeg',
            '-y',
            '-i',
            $inputPath,
            '-c:v',
            'libx264',
            '-preset',
            'ultrafast',
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

            $process = Process::timeout($this->timeout)->start($command, function (string $type, string $buffer) use ($duration) {
                if ($type === 'err' && $duration > 0) {
                    // ffmpeg outputs progress to stderr
                    if (preg_match('/time=(\d+):(\d+):(\d+\.\d+)/', $buffer, $matches)) {
                        $hours = (int) $matches[1];
                        $mins = (int) $matches[2];
                        $secs = (float) $matches[3];
                        $currentTime = ($hours * 3600) + ($mins * 60) + $secs;

                        $progress = min(99, round(($currentTime / $duration) * 100));

                        // Update progress if it increased significantly (avoiding too many writes)
                        if ($progress > ($this->download->progress + 2)) {
                            $this->download->update(['progress' => $progress]);
                        }
                    }
                }
            });

            $result = $process->wait();

            if ($result->successful()) {
                // Replace original file with optimized one
                unlink($inputPath);
                rename($outputPath, $inputPath);

                // Update file size and status
                $newSize = filesize($inputPath);
                $this->download->update([
                    'status' => DownloadStatus::COMPLETED,
                    'progress' => 100,
                    'file_size' => $newSize,
                    'downloaded_bytes' => $newSize,
                    'total_bytes' => $newSize,
                    'completed_at' => now(),
                ]);

                Log::info("OptimizeVideoJob: Optimization successful for {$this->download->filename}");
            } else {
                Log::error("OptimizeVideoJob: Optimization failed for {$this->download->filename}");
                Log::error($result->errorOutput());

                if (file_exists($outputPath)) {
                    @unlink($outputPath);
                }

                $this->download->update([
                    'status' => DownloadStatus::COMPLETED,
                    'progress' => 100,
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
                'progress' => 100,
                'error_message' => 'Optimization exception, kept original: ' . $e->getMessage(),
            ]);
        }
    }
}
