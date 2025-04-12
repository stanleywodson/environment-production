<?php

namespace App\Http\Resources;

use App\Models\CredentialFile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LargeFileResource extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{

		return [
			'id' => $this->id,
			'url' => $this->url,
			'access' => $this->access,
			'password' => $this->password,
			'application' => $this->application,
			'credential_file' => [
				'id' => $this->credential_file_id,
				'name' => $this->credentialFile->name,
				'hash' => $this->credentialFile->hash,
				'collection_date' => Carbon::parse($this->credentialFile->collection_date)->format('d/m/Y')
			],
			
		];
	}
}
