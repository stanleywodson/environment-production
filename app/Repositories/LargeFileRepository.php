<?php

namespace App\Repositories;

use Illuminate\Support\Collection;

interface LargeFileRepository
{
	public function search(
		string $query = '',
		int    $page,
		int    $perPage,
		bool   $access = true,
		bool   $password = true,
		bool   $url = true
	): Collection;
}
