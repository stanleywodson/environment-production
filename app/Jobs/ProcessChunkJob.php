<?php

namespace App\Jobs;

use App\Models\LargeFile;
use App\Observers\LargeFileObserver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class ProcessChunkJob implements ShouldQueue
{
   use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

   protected $tries = 5;

   /**
    * Create a new job instance.
    *
    * @return void
    */
   public function __construct(protected array $chunk) {}

   /**
    * Execute the job.
    *
    * @return void
    */
   public function handle()
   {
      try {
         LargeFile::insert($this->chunk);
         // LargeFileObserver::afterInsert();
      } catch (\Exception $e) {
         \Log::error('Erro ao inserir os arquivos: ' . $e->getMessage());
         $this->release(60);
      }
   }
}
