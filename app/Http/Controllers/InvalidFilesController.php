<?php

namespace App\Http\Controllers;

use App\Events\InvalidFiles;
use App\Http\Resources\InvalidFilesResource;
use App\Models\InvalidFiles as ModelsInvalidFiles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InvalidFilesController extends Controller
{
	public function index()
	{
		$invalidfiles = Cache::remember('invalidfiles', 60, function () {
			return ModelsInvalidFiles::orderBy('created_at', 'desc')->paginate(25);
		});

		return response()->json($invalidfiles);
	}

	public function store(array $data, int $userId)
	{

		$invalidfiles  = ModelsInvalidFiles::create($data);

		if (!$invalidfiles) {
			return response()->json([
				'message' => 'Invalid file not created'
			], 500);
		}

		Cache::forget('invalidfiles');

		broadcast(new InvalidFiles($userId, $invalidfiles));

		return response()->json([
			'invalidfiles' => $invalidfiles
		]);
	}

	public function destroy(Request $request)
	{
		$invalidfiles = ModelsInvalidFiles::find($request->id);

		if (!$invalidfiles) {
			return response()->json([
				'message' => 'Invalid file not found'
			], 404);
		}

		Storage::delete($invalidfiles->path);
		$invalidfiles->delete();

		return response()->json([
			'message' => 'Invalid file deleted successfully'
		]);
	}



	public function download(Request $request)
	{
		$invalidfiles = ModelsInvalidFiles::find($request->id);
		if (!$invalidfiles) {
			return response()->json(['message' => 'File not found.'], 404);
		}

		return Storage::download($invalidfiles->path);
	}
}
