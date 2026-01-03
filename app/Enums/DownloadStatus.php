<?php

namespace App\Enums;

enum DownloadStatus: string
{
    case QUEUED = 'queued';
    case DOWNLOADING = 'downloading';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match($this) {
            self::QUEUED => 'Queued',
            self::DOWNLOADING => 'Downloading',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::QUEUED => 'gray',
            self::DOWNLOADING => 'blue',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
        };
    }
}
