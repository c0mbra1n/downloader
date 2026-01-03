<?php

namespace App\Console\Commands;

use App\Models\Download;
use Illuminate\Console\Command;

class CleanOldTrash extends Command
{
    protected $signature = 'trash:clean';
    protected $description = 'Permanently delete trashed items older than 30 days';

    public function handle(): int
    {
        $cutoffDate = now()->subDays(30);

        $oldItems = Download::whereNotNull('trashed_at')
            ->where('trashed_at', '<', $cutoffDate)
            ->get();

        $count = 0;
        foreach ($oldItems as $item) {
            $path = $item->absolute_path;
            if ($path && file_exists($path)) {
                unlink($path);
            }
            $item->delete();
            $count++;
        }

        $this->info("Permanently deleted {$count} items from trash.");

        return Command::SUCCESS;
    }
}
