<?php

namespace App\Console\Commands;

use App\Models\MatchResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * clean up screenshots from storage if not in database
 */
class CleanOrphanScreenshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:screenshots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'delete screenshots file storage that not in database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $disk = Storage::disk('public');


        $dbScreenshots = MatchResult::pluck('screenshot')
            ->filter()
            ->toArray();

        $deletedFiles = 0;
        $deletedFolders = 0;

        $files = $disk->allFiles('conclusion-screenshot');
        foreach ($files as $file){
            if (!in_array($file, $dbScreenshots)) {
                $disk->delete($file);
                $this->info("Deleted orphan file: $file");
                $deletedFiles++;
            }
        }

        $directories = $disk->allDirectories('conclusion-screenshot');
        foreach ($directories as $directory) {
            if (empty($disk->files($directory)) && empty($disk->allFiles($directory))) {
                $disk->deleteDirectory($directory);
                $this->info("Deleted empty folder: {$directory}");
                $deletedFolders++;
            }
        }

        $this->info("Cleanup done: {$deletedFiles} files deleted, {$deletedFolders} folders removed.");

        return Command::SUCCESS;
    }
}
