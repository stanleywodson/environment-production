<?php

namespace Database\Seeders;

use App\Models\LargeFile;
use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
	/**
	 * Seed the application's database.
	 */
	public function run(): void
	{
		User::factory()->create(['name' => 'Admin1','email' => 'admin1@email.com','role' => 'admin','password' => bcrypt('password!3244Df')]);
		User::factory()->create(['name' => 'Admin2','email' => 'admin2@email.com','role' => 'admin','password' => bcrypt('351dl7qj')]);
		User::factory()->create(['name' => 'Admin3','email' => 'admin3@email.com','role' => 'admin','password' => bcrypt('qt519mmk')]);
	}
}
