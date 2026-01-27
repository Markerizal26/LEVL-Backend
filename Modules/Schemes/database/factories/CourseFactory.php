<?php

declare(strict_types=1);

namespace Modules\Schemes\Database\Factories;

use Bezhanov\Faker\Provider\Commerce;
use Bezhanov\Faker\Provider\Educator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Enums\CourseStatus;
use Modules\Schemes\Enums\CourseType;
use Modules\Schemes\Enums\EnrollmentType;
use Modules\Schemes\Enums\ProgressionMode;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        fake()->addProvider(new Educator(fake()));
        fake()->addProvider(new Commerce(fake()));

        $courseTitles = [
            'Complete Web Development Bootcamp',
            'Python for Data Science and Machine Learning',
            'AWS Certified Solutions Architect Training',
            'Full-Stack JavaScript Development',
            'Digital Marketing Mastery',
            'UI/UX Design Fundamentals',
            'Cybersecurity Essentials',
            'Project Management Professional (PMP) Prep',
            'React.js - The Complete Guide',
            'Docker and Kubernetes for Developers',
            'Business Analytics with Power BI',
            'Flutter Mobile App Development',
            'Advanced SQL and Database Design',
            'Laravel PHP Framework Masterclass',
            'Ethical Hacking and Penetration Testing',
            'Financial Analysis and Modeling',
            'Leadership and Management Skills',
            'Social Media Marketing Strategy',
            'DevOps Engineering on AWS',
            'Vue.js 3 Complete Course',
        ];

        $title = fake()->randomElement($courseTitles);
        $code = strtoupper(fake()->lexify('???')) . fake()->numerify('###');
        $slug = \Illuminate\Support\Str::slug($title) . '-' . uniqid();

        $descriptions = [
            "Master the fundamentals and advanced concepts in this comprehensive course. Learn through hands-on projects and real-world examples.",
            "From beginner to professional level. This course covers everything you need to know with practical applications and industry best practices.",
            "Build your expertise step by step with guided instruction, quizzes, and assignments. Perfect for career advancement.",
            "Gain in-demand skills with this intensive training program. Includes certification preparation and portfolio projects.",
            "Learn from industry experts and apply your knowledge immediately. Structured curriculum with lifetime access to materials.",
        ];

        return [
            'code' => $code,
            'title' => $title,
            'slug' => $slug,
            'short_desc' => fake()->randomElement($descriptions),
            'type' => fake()->randomElement([CourseType::Okupasi->value, CourseType::Kluster->value]),
            'level_tag' => fake()->randomElement(['dasar', 'menengah', 'mahir']),
            'enrollment_type' => fake()->randomElement([
                EnrollmentType::AutoAccept->value,
                EnrollmentType::KeyBased->value,
                EnrollmentType::Approval->value
            ]),
            'progression_mode' => fake()->randomElement([
                ProgressionMode::Sequential->value,
                ProgressionMode::Free->value
            ]),
            'status' => fake()->randomElement([
                CourseStatus::Published->value,
                CourseStatus::Draft->value
            ]),
            'tags_json' => json_encode([]),
            'prereq_text' => fake()->optional(0.4)->randomElement([
                'Basic computer skills and internet access required',
                'No prior experience needed - suitable for beginners',
                'Familiarity with basic programming concepts recommended',
                'Basic understanding of the subject matter is helpful',
                'Intermediate knowledge of related technologies preferred',
            ]),
            'published_at' => fake()->boolean(80) ? now()->subDays(rand(1, 180)) : null,
        ];
    }


    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::Published->value,
            'published_at' => now()->subDays(rand(1, 180)),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::Draft->value,
            'published_at' => null,
        ]);
    }

    public function openEnrollment(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_type' => EnrollmentType::AutoAccept->value,
        ]);
    }

    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CourseType::Okupasi->value,
        ]);
    }

    public function hybrid(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CourseType::Kluster->value,
        ]);
    }

    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'level_tag' => 'dasar',
        ]);
    }

    public function intermediate(): static
    {
        return $this->state(fn (array $attributes) => [
            'level_tag' => 'menengah',
        ]);
    }

    public function advanced(): static
    {
        return $this->state(fn (array $attributes) => [
            'level_tag' => 'mahir',
        ]);
    }
}
