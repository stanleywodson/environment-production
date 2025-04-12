<?php

namespace App\Services;

use App\Jobs\BulkIndexLargeFilesJob;
use App\Models\LargeFile;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ElasticsearchException;

class ElasticsearchLargeFileService
{
	const INDEX = 'large_files';

	public function __construct(private Client $elasticsearch) {}

	public function deleteIndex(): void
	{
		$params = ['index' => self::INDEX];
		$this->elasticsearch->indices()->delete($params);
	}

	public function createIndexWithMapping(): void
	{
		$params = [
			'index' => self::INDEX,
			'body' => [
				'mappings' => [
					'properties' => [
						'url' => [
							'type' => 'keyword',
						],
						'access' => [
							'type' => 'text',
						],
						'password' => [
							'type' => 'text',
						],
						'application' => [
							'type' => 'keyword',
						],
						'created_at' => [
							'type' => 'date',
						],
						'crendential_file_id' => [
							'type' => 'keyword',
						],
					],
				],
			],
		];

		$this->elasticsearch->indices()->create($params);
	}

	public function getExistingIds(): array
	{
		$params = [
			'index' => self::INDEX,
			'body' => [
				'query' => [
					'match_all' => new \stdClass(),
				],
				'_source' => false,
			],
		];

		$response = $this->elasticsearch->search($params);
		return array_column($response['hits']['hits'], '_id');
	}

	public function indexExists(): bool
	{
		try {
			$response = $this->elasticsearch->indices()->exists(['index' => self::INDEX]);
			return $response->asBool();
		} catch (ClientResponseException $e) {
			if ($e->getCode() === 404) {
				return false;
			}

			throw $e;
		} catch (ElasticsearchException $e) {
			throw $e;
		}
	}

	public function indexLargeFile(LargeFile $file): string
	{
		$document = [
			'index' => self::INDEX,
			'id' => $file->id,
			'body' => [
				'url' => $file->url,
				'access' => $file->access,
				'password' => $file->password,
				'application' => $file->application,
				'created_at' => $file->created_at,
				'credential_file_id' => $file->$file->credential_file_id,
			],
		];

		try {
			$result = $this->elasticsearch->index($document);
			return $result['result'];
		} catch (\Throwable $e) {
			return $e->getMessage();
		}
	}

	public function bulkIndexLargeFilesForJob(): void
	{
		$batchSize = env('CHUNK_SIZE_ELASTICSEARCH', 1000);

		LargeFile::select('id')->chunk($batchSize, function ($files) {
			$fileIds = $files->pluck('id')->toArray();

			BulkIndexLargeFilesJob::dispatch($fileIds);
		});
	}
}
