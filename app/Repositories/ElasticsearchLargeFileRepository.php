<?php

namespace App\Repositories;

use App\Http\Resources\LargeFileResource;
use App\Models\LargeFile;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class ElasticsearchLargeFileRepository implements LargeFileRepository
{
	protected $index = 'credentialsv11';

	public function __construct(private Client $elasticsearch)
	{
	}

	public function search(string $query = '', int $page, int $perPage, bool $access = true, bool $password = false, bool $url = true): Collection
	{
		$items = $this->searchOnElasticsearch($query, $page, $perPage, $access, $password, $url);

		return $this->buildCollection($items, $page, $perPage);

	}

	//v1 multi_match uso atual
	public function searchv2(array $data)
	{
		$fields = [];
		if ($data['switchState']['access']) {
			$fields[] = 'access';
		}
		
		if ($data['switchState']['password']) {
			$fields[] = 'password';
		}
		
		if ($data['switchState']['domain']) {
			$fields[] = 'access_domain.String';
		}
		
		if ($data['fullDomain']) {
			$payload = [
				"size" => 10,
				'query' => [
				'bool' => [
					'filter' => [
						[
							'term' => [
								'access.keyword' => [
									'value' => "{$data['query']}"
								],
							]
						]
					]
				]
			]
			];
		} else {

			$payload = [
				"size" => 50,
				"query" => [
					"multi_match" => [
						"query" => "{$data['query']}",
						"fields" => [...$fields],
						"type" => "best_fields",
						"operator" => "or",
						"fuzziness" => "0"
					]
				]
			];
		}

		$result = Http::withOptions([
			'verify' => false,
		])->withHeaders([
			'Authorization' => 'Basic ' . base64_encode(config('database.elastic.user') . ':' . config('database.elastic.password')),
		])->post(config('database.elastic.elk_host') . "/{$this->index}/_search", $payload);

		return $this->buildCollection($result->json(), $page = 1, $perPage = 100);

	}

	//v2 wildcard
//	public function searchv2(array $data)
//	{
//		$fields = [];
//		if ($data['switchState']['access']) {
//			$fields[] = 'access';
//		}
//		if ($data['switchState']['password']) {
//			$fields[] = 'password';
//		}
//
//		// Construir a consulta para cada campo usando wildcard
//		$shouldQueries = [];
//		foreach ($fields as $field) {
//			$shouldQueries[] = [
//				'wildcard' => [
//					$field => [
//						'value' => "*{$data['query']}*",
//						'boost' => 1.0
//					]
//				]
//			];
//		}
//
//		$queryText = [
//			"query" => [
//				"bool" => [
//					"should" => $shouldQueries,
//					"minimum_should_match" => 1
//				]
//			]
//		];
//
//		$result = Http::withOptions([
//			'verify' => false,
//		])->withHeaders([
//			'Authorization' => 'Basic ' . base64_encode(config('database.elastic.user') . ':' . config('database.elastic.password')),
//		])->post(config('database.elastic.elk_host') . "/{$this->index}/_search", $queryText);
//
//		return $this->buildCollection($result->json(), $page = 1, $perPage = 100);
//	}

//	v3 query_string
//	public function searchv2(array $data)
//	{
//		$fields = [];
//		if ($data['switchState']['access']) {
//			$fields[] = 'access';
//		}
//		if ($data['switchState']['password']) {
//			$fields[] = 'password';
//		}
//
//		$queryText = [
//			"query" => [
//				"query_string" => [
//					"query" => "*{$data['query']}*",
//					"fields" => $fields,
//					"default_operator" => "or"
//				]
//			]
//		];
//
//		$result = Http::withOptions([
//			'verify' => false,
//		])->withHeaders([
//			'Authorization' => 'Basic ' . base64_encode(config('database.elastic.user') . ':' . config('database.elastic.password')),
//		])->post(config('database.elastic.elk_host') . "/{$this->index}/_search", $queryText);
//
//		return $this->buildCollection($result->json(), $page = 1, $perPage = 100);
//	}

	//v4 query_string
//	public function searchv2(array $data)
//	{
//		$fields = [];
//		if ($data['switchState']['access']) {
//			$fields[] = 'access';
//		}
//		if ($data['switchState']['password']) {
//			$fields[] = 'password';
//		}
//
//		$fieldsString = implode(' ', $fields);
//
//		$queryText = [
//			"query" => [
//				"query_string" => [
//					"query" => "*{$data['query']}*",
//					"fields" => $fields,
//					"default_operator" => "or"
//				]
//			],
//			"size" => 100, // Limite o número de resultados por página para evitar sobrecarregar o Elasticsearch
//			"_source" => false, // Evite recuperar o campo _source completo se não for necessário
//		];
//
//		$result = Http::withOptions([
//			'verify' => false,
//		])->withHeaders([
//			'Authorization' => 'Basic ' . base64_encode(config('database.elastic.user') . ':' . config('database.elastic.password')),
//		])->post(config('database.elastic.elk_host') . "/{$this->index}/_search", $queryText);
//
//		return $this->buildCollection($result->json(), $page = 1, $perPage = 100);
//	}
	private function searchOnElasticsearch(string $query = '', int $page, int $perPage, bool $access, bool $password): array
	{
		$fields = [];
		if ($access) {
			$fields[] = "access";
		}
		if ($password) {
			$fields[] = "password";
		}

		$queryBody = [
			"query" => [
				"multi_match" => [
					"query" => $query,
					"fields" => [...$fields],
					"type" => "best_fields",
					"operator" => "or",
					"fuzziness" => "AUTO"
				]
			]
		];

		try {
			$result = Http::post(config('database.elastic.elk_host') . "/{$this->index}/_search", $queryBody);

			if ($result->successful()) {
				return $result->json();
			} else {
				return [];
			}
		} catch (\Exception $e) {
			return [];
		}
	}


	private function buildCollection(array $items, int $page, int $perPage): Collection
	{
		if (empty($items['hits']['hits'])) {
			return collect([
				'data' => [],
				'current_page' => $page,
				'per_page' => $perPage,
				'total' => 0,
				'total_pages' => 0
			]);
		}

		// Obtendo os resultados da consulta
		$ids = Arr::pluck($items['hits']['hits'], '_id');
		$totalHits = $items['hits']['total']['value'];
		$totalPages = ceil($totalHits / $perPage);

		$collection = LargeFile::select(
			'id', 'credential_file_id', 'url', 'credential_file_ids', 'access', 'password'
		)
			->with('credentialFile:id,name,hash,collection_date')
			->whereIn('id', $ids)
			->get();

		return collect([
			'data' => LargeFileResource::collection($collection),
			'current_page' => $page,
			'per_page' => $perPage,
			'total' => $totalHits,
			'total_pages' => $totalPages
		]);
	}
	
}
