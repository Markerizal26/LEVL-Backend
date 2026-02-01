<?php

namespace Modules\Gamification\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Gamification\Models\Milestone;

class MilestoneSeeder extends Seeder
{
    
    public function run(): void
    {
        $milestones = [
            [
                'code' => 'beginner',
                'name' => 'Pemula',
                'description' => 'Mulai perjalanan belajar Anda',
                'xp_required' => 100,
                'level_required' => 1,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'active_learner',
                'name' => 'Pelajar Aktif',
                'description' => 'Menunjukkan konsistensi dalam belajar',
                'xp_required' => 500,
                'level_required' => 5,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'diligent_learner',
                'name' => 'Pembelajar Tekun',
                'description' => 'Menunjukkan dedikasi tinggi',
                'xp_required' => 1000,
                'level_required' => 10,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'young_expert',
                'name' => 'Ahli Muda',
                'description' => 'Mencapai tingkat keahlian menengah',
                'xp_required' => 2500,
                'level_required' => 15,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'master',
                'name' => 'Master',
                'description' => 'Menguasai berbagai materi dengan sangat baik',
                'xp_required' => 5000,
                'level_required' => 20,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'grandmaster',
                'name' => 'Grandmaster',
                'description' => 'Pencapaian tertinggi dalam pembelajaran',
                'xp_required' => 10000,
                'level_required' => 30,
                'sort_order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($milestones as $milestone) {
            Milestone::updateOrCreate(
                ['code' => $milestone['code']],
                $milestone
            );
        }
    }
}
