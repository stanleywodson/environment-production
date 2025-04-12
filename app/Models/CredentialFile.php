<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CredentialFile extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'hash', 'collection_date', 'description'];

      public function largeFile()
      {
         return $this->hasOne(LargeFile::class);
      }
}
