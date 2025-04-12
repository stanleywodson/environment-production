<?php

namespace App\Http\Controllers;

use App\Models\LargeFile;
use App\Repositories\LargeFileRepository;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestSize\Large;

class LargeFileController extends Controller
{
	public function __construct(private LargeFileRepository $largeFileRepository)
	{
	}

	public function search(Request $request)
	{
		$request->validate([
			'query' => 'required|string|min:3',
			'switchState.*' => 'required|boolean',
		]);

		$query = $request->input('query');
		$page = (int)$request->input('page', 1);
		$perPage = (int)$request->input('per_page', 100);
		$access = $request->input('switchState.access', true);
		$password = $request->input('switchState.password', true);
		$url = $request->input('switchState.url', true);

		$result = cache()->remember("search_results_{$query}", 30, function () use ($query, $page, $perPage, $access, $password, $url) {
			return $this->largeFileRepository->search($query, $page, $perPage, $access, $password, $url);
		});


		return response()->json($result, 200);
	}

	public function searchv2(Request $request)
	{
		$request->validate([
			'query' => 'required|string|min:3',
			'switchState.*' => 'required|boolean',
			'fullDomain' => 'required|boolean'
		]);

		return response()->json($this->largeFileRepository->searchv2($request->all()));
	}

	public function searchByMailisearch(Request $request)
	{
		$request->validate([
			'query' => 'required|string|min:3',
		]);

		$query = $request->input('query');

		$largefile = LargeFile::search($query)
			->query(function ($builder) {
				$builder->select('id', 'access', 'password', 'credential_file_id');
				$builder->with('credentialFile:id,name,hash');
			})
			->paginate(4);

		return response()->json($largefile, 200);
	}
}
