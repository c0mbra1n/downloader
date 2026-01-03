<?php

namespace App\Jobs;

use App\Enums\DownloadStatus;
use App\Models\Download;
use App\Services\Aria2Service;
use App\Services\YtDlpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 7200; // 2 hours

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

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
    public function handle(Aria2Service $aria2Service, YtDlpService $ytDlpService): void
    {
        Log::info("DownloadJob started for ID: {$this->download->id}");

        // Check if download is still valid (not cancelled)
        $this->download->refresh();

        if ($this->download->status === DownloadStatus::COMPLETED) {
            Log::info("Download {$this->download->id} already completed, skipping");
            return;
        }

        if ($this->download->status === DownloadStatus::FAILED && $this->attempts() > 1) {
            // Only retry if it was marked failed by a previous attempt
            Log::info("Retrying failed download {$this->download->id}, attempt {$this->attempts()}");
        }

        try {
            // Choose the appropriate downloader based on URL
            $url = $this->download->url;

            if (YtDlpService::isVideoUrl($url)) {
                Log::info("Using yt-dlp for video URL: {$url}");
                $success = $ytDlpService->download($this->download);
            } else {
                Log::info("Using aria2c for direct URL: {$url}");
                $success = $aria2Service->download($this->download);
            }

            if (!$success) {
                Log::warning("Download failed for ID: {$this->download->id}");
            }
        } catch (\Exception $e) {
            Log::error("DownloadJob exception for ID {$this->download->id}: " . $e->getMessage());

            $this->download->update([
                'status' => DownloadStatus::FAILED,
                'error_message' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("DownloadJob permanently failed for ID {$this->download->id}: " . $exception->getMessage());

        $this->download->update([
            'status' => DownloadStatus::FAILED,
            'error_message' => 'Download failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
        ]);
    }
}
