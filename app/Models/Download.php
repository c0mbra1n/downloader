<?php

namespace App\Models;

use App\Enums\DownloadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'filename',
        'file_path',
        'file_size',
        'mime_type',
        'status',
        'progress',
        'downloaded_bytes',
        'total_bytes',
        'error_message',
        'started_at',
        'completed_at',
        'trashed_at',
        'hidden_in_queue',
    ];

    protected $casts = [
        'status' => DownloadStatus::class,
        'file_size' => 'integer',
        'progress' => 'integer',
        'downloaded_bytes' => 'integer',
        'total_bytes' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'trashed_at' => 'datetime',
        'hidden_in_queue' => 'boolean',
    ];

    /**
     * Scope for visible in queue list
     */
    public function scopeVisibleInQueue($query)
    {
        return $query->where('hidden_in_queue', false);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Get storage category based on MIME type
     */
    public function getStorageCategory(): string
    {
        $mime = $this->mime_type ?? '';

        if (str_starts_with($mime, 'video/')) {
            return 'videos';
        } elseif (str_starts_with($mime, 'audio/')) {
            return 'audios';
        } elseif (
            in_array($mime, [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
                'text/html',
            ])
        ) {
            return 'documents';
        } elseif (
            in_array($mime, [
                'application/zip',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                'application/gzip',
                'application/x-tar',
            ])
        ) {
            return 'archives';
        }

        return 'others';
    }

    /**
     * Scope for queued downloads
     */
    public function scopeQueued($query)
    {
        return $query->where('status', DownloadStatus::QUEUED);
    }

    /**
     * Scope for active (downloading) downloads
     */
    public function scopeDownloading($query)
    {
        return $query->where('status', DownloadStatus::DOWNLOADING);
    }

    /**
     * Scope for completed downloads (not trashed)
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', DownloadStatus::COMPLETED)
            ->whereNull('trashed_at');
    }

    /**
     * Scope for failed downloads
     */
    public function scopeFailed($query)
    {
        return $query->where('status', DownloadStatus::FAILED);
    }

    /**
     * Scope for trashed items
     */
    public function scopeTrashed($query)
    {
        return $query->whereNotNull('trashed_at');
    }

    /**
     * Scope for non-trashed items
     */
    public function scopeNotTrashed($query)
    {
        return $query->whereNull('trashed_at');
    }

    /**
     * Check if item is in trash
     */
    public function isTrashed(): bool
    {
        return $this->trashed_at !== null;
    }

    /**
     * Move to trash
     */
    public function moveToTrash(): void
    {
        $this->update(['trashed_at' => now()]);
    }

    /**
     * Restore from trash
     */
    public function restoreFromTrash(): void
    {
        $this->update(['trashed_at' => null]);
    }

    /**
     * Get days until permanent deletion
     */
    public function getDaysUntilDeletionAttribute(): ?int
    {
        if (!$this->trashed_at) {
            return null;
        }

        $daysInTrash = $this->trashed_at->diffInDays(now());
        return max(0, 30 - $daysInTrash);
    }

    /**
     * Check if file is playable (video/audio)
     */
    public function isPlayable(): bool
    {
        $mime = $this->mime_type ?? '';
        return str_starts_with($mime, 'video/') || str_starts_with($mime, 'audio/');
    }

    /**
     * Check if file is a video
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'video/');
    }

    /**
     * Check if file is audio
     */
    public function isAudio(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'audio/');
    }

    /**
     * Get absolute file path
     */
    public function getAbsolutePathAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return storage_path('app/' . $this->file_path);
    }
}
