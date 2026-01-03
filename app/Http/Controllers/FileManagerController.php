<?php

namespace App\Http\Controllers;

use App\Models\Download;
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
     * Move file to trash (soft delete)
     */
    public function destroy(Download $download): RedirectResponse
    {
        $download->moveToTrash();

        return redirect()->route('files.index')
            ->with('success', 'File moved to trash! Will be deleted in 30 days.');
    }
}
