<?php

namespace Database\Seeders;

use App\Models\Group;
use Database\Factories\GroupFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
		{
			Group::factory()->count(3)
				->hasDomains(5)
				->hasEmails(5)
				->create();
		}
}
