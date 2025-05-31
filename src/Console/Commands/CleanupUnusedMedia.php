<?php

namespace Mahfouz\Helpers\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CleanupUnusedMedia extends Command
{
    protected $signature = 'media:cleanup';
    protected $description = 'Delete unused media files that are not referenced in the media table';

    public function handle()
    {
        $this->info('Starting media cleanup...');

        $mediaDisk = 'public';
        $mediaPath = 'media';

        $files = Storage::disk($mediaDisk)->allFiles($mediaPath);

        $usedMedia = Media::pluck('file_name')->toArray();

        $deletedCount = 0;
        foreach ($files as $file) {
            $fileName = basename($file);
            if (!in_array($fileName, $usedMedia)) {
                Storage::disk($mediaDisk)->delete($file);
                $deletedCount++;
            }
        }

        $this->info("Deleted $deletedCount unused media files.");

        $this->deleteEmptyDirectories($mediaDisk, $mediaPath);
    }

    private function deleteEmptyDirectories($disk, $path)
    {
        $directories = Storage::disk($disk)->allDirectories($path);

        $deletedCount = 0;
        foreach ($directories as $directory) {
            if (count(Storage::disk($disk)->allFiles($directory)) === 0) {
                Storage::disk($disk)->deleteDirectory($directory);
                $deletedCount++;
            }
        }

        $this->info("Deleted $deletedCount empty directories.");
    }
}
