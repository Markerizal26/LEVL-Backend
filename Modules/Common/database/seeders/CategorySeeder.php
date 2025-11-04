<?php

namespace Modules\Common\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Common\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Teknologi Informasi', 'value' => 'information-technology'],
            ['name' => 'Manajemen Proyek', 'value' => 'project-management'],
            ['name' => 'Pemasaran', 'value' => 'marketing'],
            ['name' => 'Keuangan', 'value' => 'finance'],
            ['name' => 'Sumber Daya Manusia', 'value' => 'human-resources'],
            ['name' => 'Bahasa Inggris', 'value' => 'english-language'],
            ['name' => 'Desain', 'value' => 'design'],
        ];

        foreach ($categories as $cat) {
            Category::query()->firstOrCreate(['value' => $cat['value']], [
                'name' => $cat['name'],
                'status' => 'active',
            ]);
        }
    }
}
