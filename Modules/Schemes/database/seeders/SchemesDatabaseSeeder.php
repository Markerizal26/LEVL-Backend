<?php

namespace Modules\Schemes\Database\Seeders;

use Illuminate\Database\Seeder;

class SchemesDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds the Schemes module with:
     * 1. Tags
     * 2. 50 comprehensive courses with units and lessons
     */
    public function run(): void
    {
        $this->call([
            TagSeederEnhanced::class,
            CourseSeederEnhanced::class,
            LearningContentSeeder::class,
        ]);
    }
}
