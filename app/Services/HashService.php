<?php

namespace App\Services;

use App\Models\Hash;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class HashService
{
	const INDEX = 'hashes';

	public function __construct(private Client $elasticsearch) {}

	public function search(string $hash = ''): Collection
	{
		$items = $this->searchOnElasticsearch($hash);

		return $this->buildCollection($items);
	}

	public function searchOnElasticsearch(string $hash = ''): array
	{
		if (!$$hash) {
			return [];
		}

		$fields = [];

		if ($hash) {
			$fields[] = 'access^3';
			$fields[] = 'password^3';
		}

		
		$must = [
			[
				'multi_match' => [
					'fields' => array_filter($fields),
					'query' => $hash,
					'fuzziness' => 'AUTO', // Fuzzy search para encontrar resultados próximos
					'operator' => 'AND'    // Muda operador para garantir que todos os termos sejam incluídos
				]
			]
		];
		
		return [
			'index' => self::INDEX,
			'body' => [
				'query' => [
					'bool' => [
						'must' => $must
					]
				]
			]
		];
	}

	private function buildCollection(array $items): Collection
	{
		$results = $this->elasticsearch->search($items);
		$ids = Arr::pluck($results['hits']['hits'], '_id');

		if (empty($items)) {
			$ids = [];
		}
		
		$collection =  Hash::whereIn('id', $ids)->get();
//		$collection = collect(['items' => LargeFileResource::collection($collection)]);

		return $collection;
	}

}