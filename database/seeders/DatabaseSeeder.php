<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  use WithoutModelEvents;

  public function run(): void
  {
    $this->call([
      RoleSeeder::class,
      SuperAdminSeeder::class,
      ActiveUsersSeeder::class,
      \Modules\Common\Database\Seeders\SystemSettingSeeder::class,
      \Modules\Common\Database\Seeders\CategorySeeder::class,
      MasterDataSeeder::class,
      \Modules\Auth\Database\Seeders\AuthComprehensiveDataSeeder::class,
      \Modules\Auth\Database\Seeders\ProfileSeeder::class,
      \Modules\Schemes\Database\Seeders\SchemesDatabaseSeeder::class,
      \Modules\Enrollments\Database\Seeders\EnrollmentsDatabaseSeeder::class,
      \Modules\Learning\Database\Seeders\LearningDatabaseSeeder::class,
      \Modules\Grading\Database\Seeders\GradingDatabaseSeeder::class,
    ]);
  }
}

