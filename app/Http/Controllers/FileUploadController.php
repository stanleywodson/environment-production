<?php

namespace App\Http\Controllers;

use App\Events\AlertUploadFile;
use App\Http\Utils\Filepatterns;
use App\Models\CredentialFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
	public function __construct(
		public CredentialFileController $credentialFile,
		public Filepatterns $filepatterns
	)
	{}

	public function upload(Request $request)
	{
		$request->validate([
			'nameFile' => 'required',
			'description' => 'nullable|min:3',
			'collection_date' => 'required|date',
			'file' => 'required|file|mimes:txt,zip'
		]);

		// verifica se o arquivo é um arquivo zip
		if ($request->file('file')->getClientOriginalExtension() === 'zip') {

			$unzip = new UnzipController(
				new Filepatterns(),
				new CredentialFileController(),
				new InvalidFilesController()
			);

			return $unzip->unzip($request);
		}

		$hash = $this->credentialFile->generateHash($request->file('file'));

		if ($this->credentialFile->checkHashExists($hash)) {
			return response()->json(['message' => 'O arquivo já foi enviado anteriormente.'], 400);
		}

		$credentials = CredentialFile::create([
			'name' => $request->input('nameFile'),
			'collection_date' => $request->input('collection_date'),
			'description' => $request->input('description'),
			'hash' => $hash,
		]);

		$filePath = $request->file->store('uploads');
		$storegePath = storage_path('app/' . $filePath);
		$result = $this->filepatterns->verifyIsValidArchive($storegePath);

		if (isset($result['withoutpatterns']) && $result['withoutpatterns'] === true) {
			if (Storage::exists('uploads')) {
				Storage::deleteDirectory('uploads');
			}
			$credentials->delete();
			return response()->json(['message' => 'O arquivo não é um arquivo válido.'], 400);
		}

		$this->filepatterns->insertByDefault($result, $storegePath, $credentials->id);

		if (Storage::exists('uploads')) {
			Storage::deleteDirectory('uploads');
		}
		broadcast(new AlertUploadFile(auth()->user()->id, false, 'Os arquivos estão sendo processados em fila.'));
		return response()->noContent();
	}
}
