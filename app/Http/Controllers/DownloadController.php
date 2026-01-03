<?php

namespace App\Http\Controllers;

use App\Enums\DownloadStatus;
use App\Jobs\DownloadJob;
use App\Models\Download;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DownloadController extends Controller
{
    /**
     * Display all downloads
     */
    public function index(): View
    {
        $downloads = Download::orderBy('created_at', 'desc')->paginate(20);

        return view('downloads.index', compact('downloads'));
    }

    /**
     * Store a new download request
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
        ], [
            'url.required' => 'URL is required',
            'url.url' => 'Please enter a valid URL',
            'url.max' => 'URL is too long',
        ]);

        // Check simultaneous download limit
        $activeDownloads = Download::whereIn('status', [
            DownloadStatus::QUEUED,
            DownloadStatus::DOWNLOADING,
        ])->count();

        $maxSimultaneous = (int) config('download.max_simultaneous', 5);

        if ($activeDownloads >= $maxSimultaneous) {
            return back()->withErrors([
                'url' => "Maximum {$maxSimultaneous} simultaneous downloads allowed. Please wait for current downloads to complete.",
            ])->withInput();
        }

        // Create download record
        $download = Download::create([
            'url' => $validated['url'],
            'status' => DownloadStatus::QUEUED,
        ]);

        // Dispatch job to queue
        DownloadJob::dispatch($download);

        return redirect()->route('downloads.index')
            ->with('success', 'Download queued successfully!');
    }

    /**
     * Get download status (for polling)
     */
    public function status(Download $download): JsonResponse
    {
        return response()->json([
            'id' => $download->id,
            'status' => $download->status->value,
            'status_label' => $download->status->label(),
            'status_color' => $download->status->color(),
            'progress' => $download->progress,
            'downloaded_bytes' => $download->downloaded_bytes,
            'total_bytes' => $download->total_bytes,
            'filename' => $download->filename,
            'file_size' => $download->file_size,
            'formatted_size' => $download->formatted_size,
            'error_message' => $download->error_message,
        ]);
    }

    /**
     * Get active downloads status (for smart batch polling)
     */
    public function statusAll(): JsonResponse
    {
        // Optimization: Only poll active downloads + last 10 records
        $activeStatusIds = [
            DownloadStatus::QUEUED->value,
            DownloadStatus::DOWNLOADING->value
        ];

        // Fetch IDs first to avoid MySQL subquery limit restrictions
        $recentIds = Download::orderBy('created_at', 'desc')->limit(10)->pluck('id')->toArray();

        $downloads = Download::whereIn('status', $activeStatusIds)
            ->orWhereIn('id', $recentIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($download) {
                return [
                    'id' => $download->id,
                    'url' => $download->url,
                    'status' => $download->status->value,
                    'status_label' => $download->status->label(),
                    'status_color' => $download->status->color(),
                    'progress' => $download->progress,
                    'downloaded_bytes' => $download->downloaded_bytes,
                    'total_bytes' => $download->total_bytes,
                    'filename' => $download->filename,
                    'file_size' => $download->file_size,
                    'formatted_size' => $download->formatted_size,
                    'error_message' => $download->error_message,
                    'created_at' => $download->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json(['downloads' => $downloads]);
    }

    /**
     * Retry a failed download
     */
    public function retry(Download $download): RedirectResponse
    {
        if ($download->status !== DownloadStatus::FAILED) {
            return back()->withErrors(['error' => 'Only failed downloads can be retried']);
        }

        $download->update([
            'status' => DownloadStatus::QUEUED,
            'progress' => 0,
            'downloaded_bytes' => 0,
            'error_message' => null,
        ]);

        DownloadJob::dispatch($download);

        return redirect()->route('downloads.index')
            ->with('success', 'Download requeued successfully!');
    }

    /**
     * Delete a download record (does NOT delete the file)
     */
    public function destroy(Download $download): RedirectResponse
    {
        $download->delete();

        return redirect()->route('downloads.index')
            ->with('success', 'Download record removed!');
    }
}
