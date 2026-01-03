<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Show settings page
     */
    public function index(): View
    {
        $cookiesPath = storage_path('app/cookies.txt');
        $cookiesContent = file_exists($cookiesPath) ? file_get_contents($cookiesPath) : '';
        $cookiesExists = !empty(trim($cookiesContent));

        return view('settings.index', compact('cookiesContent', 'cookiesExists'));
    }

    /**
     * Save cookies content
     */
    public function saveCookies(Request $request): RedirectResponse
    {
        $request->validate([
            'cookies' => 'nullable|string',
        ]);

        $cookiesPath = storage_path('app/cookies.txt');
        $content = $request->input('cookies', '');

        file_put_contents($cookiesPath, $content);

        if (empty(trim($content))) {
            return back()->with('success', 'Cookies berhasil dihapus!');
        }

        return back()->with('success', 'Cookies berhasil disimpan!');
    }
}
