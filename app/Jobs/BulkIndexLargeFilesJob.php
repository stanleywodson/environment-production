<?php

namespace App\Jobs;

use Elastic\Elasticsearch\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Models\LargeFile;

class BulkIndexLargeFilesJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected const INDEX = 'large_files';
	protected $tries = 2; // Número máximo de tentativas

	public function __construct(protected array $fileIds)
	{
	}

	public function handle(Client $elasticsearch): void
	{
		$params = ['body' => []];

// Se o número de arquivos for grande, use chunk para reduzir o consumo de memória
		LargeFile::whereIn('id', $this->fileIds)
			->select(['id', 'url', 'access', 'password', 'application', 'created_at', 'credential_file_id'])
			->chunk(100, function ($files) use (&$params) {
				$chunkParams = $files->flatMap(function ($file) {
					return [
						[
							'index' => [
								'_index' => self::INDEX,
								'_id' => $file->id,
							],
						],
						[
							'url' => $file->url,
							'access' => $file->access,
							'password' => $file->password,
							'application' => $file->application,
							'created_at' => $file->created_at,
							'credential_file_id' => $file->credential_file_id,
						],
					];
				})->toArray();

				$params['body'] = array_merge($params['body'], $chunkParams);
			});

		if (!empty($params['body'])) {
			try {
				$elasticsearch->bulk($params);
			} catch (\Exception $e) {
				\Log::error('Erro ao indexar lote no Elasticsearch: ' . $e->getMessage());
				$this->release(60);
			}
		}
	}

	public function middleware(): array
	{
		$lockKey = 'bulk_index_' . md5(implode('_', $this->fileIds));
		return [(new WithoutOverlapping($lockKey))->expireAfter(120)];
	}
}
