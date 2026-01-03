<?php

namespace App\Http\Controllers;

use App\Models\Download;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TrashController extends Controller
{
    /**
     * Display trash
     */
    public function index(): View
    {
        $trashedItems = Download::trashed()
            ->orderBy('trashed_at', 'desc')
            ->get();

        $totalSize = $trashedItems->sum('file_size');

        if ($totalSize >= 1073741824) {
            $formattedSize = number_format($totalSize / 1073741824, 2) . ' GB';
        } elseif ($totalSize >= 1048576) {
            $formattedSize = number_format($totalSize / 1048576, 2) . ' MB';
        } elseif ($totalSize >= 1024) {
            $formattedSize = number_format($totalSize / 1024, 2) . ' KB';
        } else {
            $formattedSize = $totalSize . ' B';
        }

        return view('trash.index', compact('trashedItems', 'formattedSize'));
    }

    /**
     * Restore item from trash
     */
    public function restore(Download $download): RedirectResponse
    {
        if (!$download->isTrashed()) {
            return back()->withErrors(['error' => 'Item not in trash']);
        }

        $download->restoreFromTrash();

        return redirect()->route('trash.index')
            ->with('success', 'File restored successfully!');
    }

    /**
     * Permanently delete item
     */
    public function destroy(Download $download): RedirectResponse
    {
        $path = $download->absolute_path;

        if ($path && file_exists($path)) {
            unlink($path);
        }

        $download->delete();

        return redirect()->route('trash.index')
            ->with('success', 'File permanently deleted!');
    }

    /**
     * Empty all trash
     */
    public function emptyTrash(): RedirectResponse
    {
        $trashedItems = Download::trashed()->get();

        foreach ($trashedItems as $item) {
            $path = $item->absolute_path;
            if ($path && file_exists($path)) {
                unlink($path);
            }
            $item->delete();
        }

        return redirect()->route('trash.index')
            ->with('success', 'Trash emptied successfully!');
    }
}
