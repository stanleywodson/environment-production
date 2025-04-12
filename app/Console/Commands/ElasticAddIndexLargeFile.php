<?php

namespace App\Console\Commands;

use App\Services\ElasticsearchLargeFileService;
use Illuminate\Console\Command;

class ElasticAddIndexLargeFile extends Command
{
   protected $signature = 'add-index';

   protected $description = 'Elastic add index file';

   public function handle(ElasticsearchLargeFileService $elastic)
   {
      if (!$elastic->indexExists('large_files')) {
         $elastic->createIndexWithMapping();
      }

      $this->info('Iniciando a indexação em massa dos arquivos grandes...');
      try {
         $elastic->bulkIndexLargeFilesForJob();
         $this->info('Indexação concluída com sucesso!');
      } catch (\Exception $e) {
         $this->error('Erro ao indexar os arquivos: ' . $e->getMessage());
      }
   }
}
