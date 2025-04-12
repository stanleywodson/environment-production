<?php

namespace App\Console\Commands;

use App\Jobs\ConsolidateRecordsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConsolidateRecords extends Command
{
	protected $signature = 'app:consolidate-records';

	public function handle()
	{
		DB::table('large_files')
			->select('access', 'password', 'credential_file_id')
			->groupBy('access', 'password', 'credential_file_id')
			->havingRaw('COUNT(*) > 1')
			->orderBy('access')
			->orderBy('password')
			->chunk(1000, function ($records) {
				
				
				dispatch(new ConsolidateRecordsJob($records));

			});

		$this->info('Records successfully queued!');
	}
}