<?php

namespace App\Http\Controllers;

use App\Models\CredentialFile;
use Illuminate\Support\Facades\Cache;

class CredentialFileController extends Controller
{
	public function create(array $data)
	{
		return CredentialFile::create($data);
	}

	public function checkHashExists(string $hash): bool
	{
		return CredentialFile::where('hash', $hash)->exists();

	}

	public function generateHash($file)
	{
		// Calcular o hash SHA-256 do arquivo
		$fileContent = file_get_contents($file->getRealPath());
		return hash('sha256', $fileContent);
	}
}
