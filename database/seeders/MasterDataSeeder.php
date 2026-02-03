<?php

namespace Database\Seeders;

use Modules\Common\Models\MasterDataItem;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $this->seedCategories();
    $this->seedDifficultyLevels();
    $this->seedContentTypes();
  }

  private function seedCategories(): void
  {
    $categories = [
      ["value" => "information-technology", "label" => "Teknologi Informasi"],
      ["value" => "project-management", "label" => "Manajemen Proyek"],
      ["value" => "marketing", "label" => "Pemasaran"],
      ["value" => "finance", "label" => "Keuangan"],
      ["value" => "human-resources", "label" => "Sumber Daya Manusia"],
      ["value" => "english-language", "label" => "Bahasa Inggris"],
      ["value" => "design", "label" => "Desain"],
    ];

    foreach ($categories as $index => $item) {
      MasterDataItem::firstOrCreate(
        [
          "type" => "categories",
          "value" => $item["value"],
        ],
        [
          "label" => $item["label"],
          "is_active" => true,
          "sort_order" => $index + 1,
        ],
      );
    }
  }

  private function seedDifficultyLevels(): void
  {
    $difficultyLevels = [
      ["value" => "beginner", "label" => "Beginner", "label_id" => "Pemula"],
      ["value" => "intermediate", "label" => "Intermediate", "label_id" => "Menengah"],
      ["value" => "advanced", "label" => "Advanced", "label_id" => "Lanjutan"],
    ];

    foreach ($difficultyLevels as $index => $item) {
      MasterDataItem::firstOrCreate(
        [
          "type" => "difficulty-levels",
          "value" => $item["value"],
        ],
        [
          "label" => $item["label_id"],
          "is_active" => true,
          "sort_order" => $index + 1,
          "metadata" => ["label_en" => $item["label"]],
        ],
      );
    }
  }

  private function seedContentTypes(): void
  {
    $contentTypes = [
      ["value" => "article", "label" => "Article"],
      ["value" => "video", "label" => "Video"],
      ["value" => "quiz", "label" => "Quiz"],
    ];

    foreach ($contentTypes as $index => $item) {
      MasterDataItem::firstOrCreate(
        [
          "type" => "content-types",
          "value" => $item["value"],
        ],
        [
          "label" => $item["label"],
          "is_active" => true,
          "sort_order" => $index + 1,
        ],
      );
    }
  }

  private static function getRandomColor(): string
  {
    $colors = ["red", "blue", "green", "yellow", "purple", "orange"];
    return $colors[array_rand($colors)];
  }
}
