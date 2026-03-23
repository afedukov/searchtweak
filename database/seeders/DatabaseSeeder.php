<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Run all default seeders:
     *   php artisan db:seed
     *
     * Run only the demo dataset:
     *   php artisan db:seed --class=DemoMarketplaceSeeder
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            SearchDataSeeder::class,
            EvaluationSeeder::class,
            JudgeSeeder::class,
        ]);
    }
}
