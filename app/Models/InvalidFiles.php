<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvalidFiles extends Model
{
    use HasFactory;

      protected $fillable = [
         'name',
         'path',
         'file_name',
         'collection_date'
      ];
}
