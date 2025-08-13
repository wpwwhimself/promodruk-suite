<?php

namespace App\Jobs;

use App\Http\Controllers\DocumentOutputController;
use App\Models\OfferFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateOfferFilesJob implements ShouldQueue
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
        Log::info("💾 Searching for pending offers");
        $file = OfferFile::prepareQueue()->get()->first();
        if (!$file) {
            Log::info("💾 No pending offers found");
            return;
        }

        Log::info("💾 Downloading offer", ["offer" => $file->offer_id, "type" => $file->type]);
        $filename = app(DocumentOutputController::class)->downloadOffer($file->type, $file->offer_id, true);
        $file->update(["file_path" => $filename]);
        Log::info("💾 Offer downloaded", ["path" => $file->file_path]);
    }
}
