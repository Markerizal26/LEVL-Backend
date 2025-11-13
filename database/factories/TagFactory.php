<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Schemes\Models\Tag;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Schemes\Models\Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word();
        $slug = Str::slug($name);

        return [
            'name' => $name,
            'slug' => $slug,
        ];
    }
}

