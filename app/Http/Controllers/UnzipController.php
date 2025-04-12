<?php

namespace App\Http\Controllers;

use App\Events\AlertUploadFile;
use App\Events\ProgressUpdated;
use App\Http\Utils\Filepatterns;
use App\Models\CredentialFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class UnzipController extends Controller
{
	public function __construct(
		public Filepatterns $filepatterns,
		public CredentialFileController $credentialFile,
		public InvalidFilesController $invalidFiles
	) {}

	public function unzip(Request $request)
	{
		$userId = auth()->user()->id;
		$file = $request->file('file');
		$path = $file->store('uploads');

		$hash = $this->credentialFile->generateHash($request->file('file'));

		if ($this->credentialFile->checkHashExists($hash)) {
			if (Storage::exists('uploads')) {
				Storage::deleteDirectory('uploads');
			}
			return response()->json(['message' => 'O arquivo já foi enviado anteriormente.'], 400);
		}

		$zip = new ZipArchive();

		if ($zip->open(storage_path('app/' . $path)) !== TRUE) {
			return response()->json(['message' => 'Falha ao abrir o arquivo!']);
		}

		broadcast(new AlertUploadFile($userId, true, 'Extração dos arquivos...', 'error'));

		$extractPath = storage_path('app/uploads');
		$zip->extractTo($extractPath);
		//extraindo
		$zip->close();

		broadcast(new AlertUploadFile($userId, false, 'Os arquivos estão sendo processados em fila.'));

		$validFiles = [];

		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($extractPath));

		foreach ($iterator as $file) {
			if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'txt') {
				$filePath = $file->getPathname();

				$result = $this->filepatterns->verifyIsValidArchive($filePath);

				if (isset($result['withoutpatterns']) && $result['withoutpatterns'] === true) {

					$relativeFilePath = str_replace(storage_path('app') . 'UnzipController.php/', '', $filePath);
					Storage::move($relativeFilePath, 'invalidfiles/' . basename($filePath));

					// novo path
					$path = 'invalidfiles/' . basename($filePath);

					$this->invalidFiles->store([
						'name' => $file->getFilename(),
						'path' => $path,
						'file_name' => $request->input('nameFile'),
						'collection_date' => $request->input('collection_date'),
					], $userId);

					continue;
				}

				$hash = $this->credentialFile->generateHash($file);

				if ($this->credentialFile->checkHashExists($hash)) {
					continue;
				}

				$credentials = CredentialFile::create([
					'name' => $request->input('nameFile'),
					'collection_date' => $request->input('collection_date'),
					'description' => $request->input('description'),
					'hash' => $hash,
				]);

				$this->filepatterns->insertByDefault($result, $filePath, $credentials->id);

				if (
					($result['avancedFormat'] ?? false) ||
					($result['simpleSeparator'] ?? false)
				) {

					$validFiles[] = $filePath;
				} else {

					$this->invalidFiles->store([
						'name' => $file->getFilename(),
						'path' => $filePath,
						'file_name' => $file->getFilename(),
					], $userId);
				}
			}
		}

		if (Storage::exists('uploads')) {
			Storage::deleteDirectory('uploads');
		}

		if (empty($validFiles)) {
			return response()->json(['message' => 'Os arquivos não são válidos ou já foram inseridos.'], 400);
		}

		return response()->noContent();
	}
}
