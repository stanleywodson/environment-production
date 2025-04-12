<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
	use HasFactory;

	protected $fillable = ['group_id', 'domain'];

	public function group()
	{
		return $this->belongsTo(Group::class);
	}


}
