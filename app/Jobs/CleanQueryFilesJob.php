<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanQueryFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Cleaning query files...");

        foreach ([
            ["temporary", "public/attachments/temp"],
            ["sent", "public/attachments"],
        ] as [$type, $path]) {
            foreach (Storage::allFiles($path) as $file) {
                if (Carbon::parse(Storage::lastModified($file))->gt(
                    now()->subHours(getSetting("old_query_files_hours_$type"))
                )) continue;
                Log::debug("QF> - Deleting $file");
                Storage::delete($file);
            }

            foreach (Storage::allDirectories($path) as $dir) {
                if (count(Storage::files($dir))) continue;
                Storage::deleteDirectory($dir);
            }
        }

        Log::info("QF> - Done");
    }
}
