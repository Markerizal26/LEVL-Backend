<?php

namespace Modules\Common\Database\Seeders;

use App\Models\ActivityLog;
use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first(); // Assuming seeders run after user seeders
        if (!$admin) return;

        $actions = ['login', 'logout', 'created', 'updated', 'deleted'];
        $logNames = ['auth', 'system', 'user', 'course'];
        $browsers = ['Chrome', 'Firefox', 'Safari', 'Edge'];
        $platforms = ['Windows', 'macOS', 'Linux', 'iOS', 'Android'];

        for ($i = 0; $i < 50; $i++) {
            $logName = $logNames[array_rand($logNames)];
            $action = $actions[array_rand($actions)];
            
            ActivityLog::create([
                'log_name' => $logName,
                'description' => "User {$action} a resource",
                'subject_type' => User::class,
                'subject_id' => $admin->id,
                'causer_type' => User::class,
                'causer_id' => $admin->id,
                'properties' => [
                    'browser' => $browsers[array_rand($browsers)],
                    'platform' => $platforms[array_rand($platforms)],
                    'device_type' => array_rand(['desktop' => 1, 'mobile' => 1]),
                    'ip' => '127.0.0.1',
                    'city' => array_rand(['Jakarta' => 1, 'Bandung' => 1, 'Surabaya' => 1]),
                    'region' => 'DKI Jakarta',
                    'country' => 'Indonesia',
                ],
                'created_at' => now()->subDays(rand(0, 30)),
            ]);
        }
    }
}
