<?php

namespace App\Observers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class LargeFileObserver
{
    static function afterInsert()
    {
      Log::info('Indexação de arquivo grande iniciada.');
      Artisan::call('add-index');
    }
}
