<?php

namespace App\Models;

use App\Observers\LargeFileObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

#[ObservedBy([LargeFileObserver::class])]
class LargeFile extends Model
{
    use HasFactory, Searchable;

    protected $fillable = ['url','access','password', 'application', 'credential_file_id'];

      public function credentialFile()
      {
         return $this->belongsTo(CredentialFile::class);
      }
			
			public function toSearchableArray()
			{
				return [
					'id' => $this->id,
					'access' => $this->access,
					'password' => $this->password,
				];
			}
			
			

}
