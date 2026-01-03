<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.submit');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', fn() => redirect()->route('downloads.index'));

    // Auth
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('password/change', [LoginController::class, 'changePassword'])->name('password.update');

    // Downloads
    Route::get('downloads', [DownloadController::class, 'index'])->name('downloads.index');
    Route::post('downloads', [DownloadController::class, 'store'])->name('downloads.store');
    Route::get('downloads/{download}/status', [DownloadController::class, 'status'])->name('downloads.status');
    Route::get('downloads/status-all', [DownloadController::class, 'statusAll'])->name('downloads.statusAll');
    Route::post('downloads/{download}/retry', [DownloadController::class, 'retry'])->name('downloads.retry');
    Route::delete('downloads/{download}', [DownloadController::class, 'destroy'])->name('downloads.destroy');

    // File Manager
    Route::get('files', [FileManagerController::class, 'index'])->name('files.index');
    Route::get('files/{download}/download', [FileManagerController::class, 'download'])->name('files.download');
    Route::delete('files/{download}', [FileManagerController::class, 'destroy'])->name('files.destroy');

    // Trash
    Route::get('trash', [TrashController::class, 'index'])->name('trash.index');
    Route::post('trash/{download}/restore', [TrashController::class, 'restore'])->name('trash.restore');
    Route::delete('trash/{download}', [TrashController::class, 'destroy'])->name('trash.destroy');
    Route::delete('trash', [TrashController::class, 'emptyTrash'])->name('trash.empty');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings/cookies', [SettingsController::class, 'saveCookies'])->name('settings.cookies');

    // Media Streaming
    Route::get('stream/{download}', [StreamController::class, 'stream'])->name('stream');
});
