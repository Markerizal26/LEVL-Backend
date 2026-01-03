<?php

namespace Modules\Schemes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\CourseTag;
use Modules\Schemes\Models\Tag;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Schemes\Models\CourseTag>
 */
class CourseTagFactory extends Factory
{
    protected $model = CourseTag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'tag_id' => Tag::factory(),
        ];
    }
}
