<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\StreamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('login', fn() => view('auth.login'))->name('login');
    Route::post('login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login.submit');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', fn() => redirect()->route('downloads.index'));

    // Logout
    Route::post('logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

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

    // Media Streaming
    Route::get('stream/{download}', [StreamController::class, 'stream'])->name('stream');
});
