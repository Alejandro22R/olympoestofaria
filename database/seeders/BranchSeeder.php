<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::query()->firstOrCreate(['name' => 'Olympo Estofaria'], ['city' => 'São Paulo', 'is_active' => true]);
    }
}
