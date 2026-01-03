<?php

namespace Modules\Learning\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Learning\Models\Submission;
use Modules\Learning\Models\SubmissionFile;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Learning\Models\SubmissionFile>
 */
class SubmissionFileFactory extends Factory
{
    protected $model = SubmissionFile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
        ];
    }
}
