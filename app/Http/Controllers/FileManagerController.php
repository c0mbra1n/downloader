<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Enums\DownloadStatus;
use App\Jobs\OptimizeVideoJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileManagerController extends Controller
{
    /**
     * Display file manager
     */
    public function index(Request $request): View
    {
        $category = $request->get('category', 'all');

        $categories = [
            'all' => 'All Files',
            'videos' => 'Videos',
            'audios' => 'Audio',
            'documents' => 'Documents',
            'archives' => 'Archives',
            'others' => 'Others',
        ];

        $query = Download::completed()->orderBy('completed_at', 'desc');

        if ($category !== 'all') {
            $query->where('file_path', 'like', "downloads/{$category}/%");
        }

        $files = $query->get();

        // Get category counts
        $counts = [
            'all' => Download::completed()->count(),
            'videos' => Download::completed()->where('file_path', 'like', 'downloads/videos/%')->count(),
            'audios' => Download::completed()->where('file_path', 'like', 'downloads/audios/%')->count(),
            'documents' => Download::completed()->where('file_path', 'like', 'downloads/documents/%')->count(),
            'archives' => Download::completed()->where('file_path', 'like', 'downloads/archives/%')->count(),
            'others' => Download::completed()->where('file_path', 'like', 'downloads/others/%')->count(),
        ];

        return view('files.index', compact('files', 'categories', 'category', 'counts'));
    }

    /**
     * Download a file
     */
    public function download(Download $download): BinaryFileResponse|RedirectResponse
    {
        $path = $download->absolute_path;

        if (!$path || !file_exists($path)) {
            return back()->withErrors(['error' => 'File not found']);
        }

        return response()->download($path, $download->filename);
    }

    /**
     * Upload a file
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:5242880', // max 5GB
        ]);

        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Use Download model to determine category
        $tempDownload = new Download(['mime_type' => $mimeType]);
        $category = $tempDownload->getStorageCategory();

        $targetDir = "downloads/{$category}";
        $path = $file->storeAs($targetDir, $filename, 'local');

        // Initial status - if it's a video, we want to optimize it for web
        $status = DownloadStatus::COMPLETED;
        if ($category === 'videos') {
            $status = DownloadStatus::PROCESSING;
        }

        // Create download record
        $download = Download::create([
            'url' => 'uploaded://' . $filename,
            'filename' => $filename,
            'file_path' => $path,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'status' => $status,
            'progress' => ($status === DownloadStatus::COMPLETED) ? 100 : 0,
            'downloaded_bytes' => $fileSize,
            'total_bytes' => $fileSize,
            'completed_at' => ($status === DownloadStatus::COMPLETED) ? now() : null,
        ]);

        if ($status === DownloadStatus::PROCESSING) {
            OptimizeVideoJob::dispatch($download);
        }

        return response()->json([
            'success' => true,
            'message' => ($status === DownloadStatus::PROCESSING)
                ? 'File uploaded! Optimizing for web playback...'
                : 'File uploaded successfully',
            'download' => $download
        ]);
    }

    /**
     * Start manual optimization for a video
     */
    public function optimize(Download $download): RedirectResponse
    {
        if (!$download->isVideo()) {
            return back()->with('error', 'Only videos can be optimized.');
        }

        $download->update([
            'status' => DownloadStatus::PROCESSING,
            'progress' => 0,
            'error_message' => null
        ]);

        OptimizeVideoJob::dispatch($download);

        return back()->with('success', 'Optimization started in the background.');
    }

    /**
     * Move file to trash (soft delete)
     */
    public function destroy(Download $download): RedirectResponse
    {
        $download->moveToTrash();

        return redirect()->route('files.index')
            ->with('success', 'File moved to trash! Will be deleted in 30 days.');
    }
}
