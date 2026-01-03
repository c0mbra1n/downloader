<?php

namespace App\Enums;

enum DownloadStatus: string
{
    case QUEUED = 'queued';
    case DOWNLOADING = 'downloading';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'Queued',
            self::DOWNLOADING => 'Downloading',
            self::PROCESSING => 'Optimizing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::QUEUED => 'gray',
            self::DOWNLOADING => 'blue',
            self::PROCESSING => 'purple',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
        };
    }
}
