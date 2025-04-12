<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ConsolidateRecordsJob implements ShouldQueue
{

   public function __construct(protected $registros)
   {
   }

   public function handle()
   {
       foreach ($this->registros as $registro) {
           // Recupera todos os registros correspondentes
           $ids = DB::table('large_files')
               ->where('access', $registro->access)
               ->where('password', $registro->password)
               ->pluck('id')
               ->toArray();

           // Recupera o primeiro ID como o principal
           $idPrincipal = array_shift($ids);

           // Recupera o credential_file_id do novo registro
           $newCredentialFileId = $registro->credential_file_id;

           if ($idPrincipal) {
               // Obtém os credential_file_ids existentes do registro principal
               $existingCredentialFileIds = DB::table('large_files')
                   ->where('id', $idPrincipal)
                   ->value('credential_file_ids');

               // Converte os IDs existentes em um array (assumindo que está armazenado como JSON)
               $existingCredentialFileIds = $existingCredentialFileIds ? json_decode($existingCredentialFileIds, true) : [];

               // Adiciona o novo credential_file_id, se ele ainda não estiver na lista
               if (!in_array($newCredentialFileId, $existingCredentialFileIds)) {
                   $existingCredentialFileIds[] = $newCredentialFileId;
               }

               // Atualiza o registro principal com a nova lista de credential_file_ids
               DB::table('large_files')
                   ->where('id', $idPrincipal)
                   ->update(['credential_file_ids' => json_encode($existingCredentialFileIds)]);
           }

           // Exclui os registros duplicados (aqueles que não são o ID principal)
           if (count($ids) > 0) {
               DB::table('large_files')
                   ->whereIn('id', $ids)
                   ->delete();
           }
       }
   }

}
