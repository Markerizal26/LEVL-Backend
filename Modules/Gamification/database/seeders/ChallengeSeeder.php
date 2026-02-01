<?php

namespace Modules\Gamification\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Gamification\Models\Challenge;

class ChallengeSeeder extends Seeder
{
    public function run(): void
    {
        \DB::connection()->disableQueryLog();
        
        
        $dailyChallenges = [
            [
                'title' => 'Pejuang Harian',
                'description' => 'Selesaikan 3 lesson hari ini untuk mendapatkan bonus XP!',
                'type' => 'daily',
                'criteria' => [
                    'type' => 'lessons_completed',
                    'target' => 3,
                ],
                'target_count' => 3,
                'points_reward' => 50,
            ],
            [
                'title' => 'Pengumpul Tugas',
                'description' => 'Kumpulkan 1 tugas hari ini.',
                'type' => 'daily',
                'criteria' => [
                    'type' => 'assignments_submitted',
                    'target' => 1,
                ],
                'target_count' => 1,
                'points_reward' => 30,
            ],
            [
                'title' => 'Latihan Rutin',
                'description' => 'Selesaikan 2 latihan soal hari ini.',
                'type' => 'daily',
                'criteria' => [
                    'type' => 'exercises_completed',
                    'target' => 2,
                ],
                'target_count' => 2,
                'points_reward' => 40,
            ],
        ];

        
        $weeklyChallenges = [
            [
                'title' => 'Pembelajar Mingguan',
                'description' => 'Selesaikan 10 lesson minggu ini untuk bonus besar!',
                'type' => 'weekly',
                'criteria' => [
                    'type' => 'lessons_completed',
                    'target' => 10,
                ],
                'target_count' => 10,
                'points_reward' => 200,
            ],
            [
                'title' => 'Kolektor XP',
                'description' => 'Kumpulkan 500 XP minggu ini.',
                'type' => 'weekly',
                'criteria' => [
                    'type' => 'xp_earned',
                    'target' => 500,
                ],
                'target_count' => 500,
                'points_reward' => 150,
            ],
            [
                'title' => 'Master Latihan',
                'description' => 'Selesaikan 5 latihan soal minggu ini.',
                'type' => 'weekly',
                'criteria' => [
                    'type' => 'exercises_completed',
                    'target' => 5,
                ],
                'target_count' => 5,
                'points_reward' => 100,
            ],
        ];

        foreach (array_merge($dailyChallenges, $weeklyChallenges) as $challenge) {
            Challenge::updateOrCreate(
                ['title' => $challenge['title']],
                $challenge
            );
        }

        $this->command->info('Sample challenges seeded successfully!');
        
        gc_collect_cycles();
        \DB::connection()->enableQueryLog();
    }
}
