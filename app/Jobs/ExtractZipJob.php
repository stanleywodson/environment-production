<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ZipArchive;

class ExtractZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $zipPath;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($zipPath)
    {
        $this->zipPath = $zipPath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $zip = new ZipArchive;
        if ($zip->open($this->zipPath) === TRUE) {
            $extractPath = storage_path('app/uploads');
            $zip->extractTo($extractPath);
            $zip->close();
        } else {
            // Lidar com erro de abertura do arquivo zip
        }
    }
}