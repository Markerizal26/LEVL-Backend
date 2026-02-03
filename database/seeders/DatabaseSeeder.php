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
      \Modules\Common\Database\Seeders\CommonDatabaseSeeder::class,
      \Modules\Common\Database\Seeders\ActivityLogSeeder::class,
      \Modules\Common\Database\Seeders\AuditLogSeeder::class,
      MasterDataSeeder::class,
      \Modules\Auth\Database\Seeders\AuthComprehensiveDataSeeder::class,
      \Modules\Auth\Database\Seeders\ProfileSeeder::class,
      \Modules\Schemes\Database\Seeders\SchemesDatabaseSeeder::class,
      \Modules\Enrollments\Database\Seeders\EnrollmentsDatabaseSeeder::class,
      \Modules\Learning\Database\Seeders\LearningDatabaseSeeder::class,
      \Modules\Gamification\Database\Seeders\GamificationDatabaseSeeder::class,
      \Modules\Grading\Database\Seeders\GradingDatabaseSeeder::class,
    ]);
  }
}

