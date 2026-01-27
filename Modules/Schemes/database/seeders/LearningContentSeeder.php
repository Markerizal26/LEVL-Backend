<?php

declare(strict_types=1);

namespace Modules\Schemes\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\LessonBlock;

class LearningContentSeeder extends Seeder
{
    private const UNITS_PER_COURSE = [5, 8];
    private const LESSONS_PER_UNIT = [8, 15];
    private const BLOCKS_PER_LESSON = [5, 12];

    public function run(): void
    {
        $this->command->info("\n📖 Creating learning content hierarchy...");
        $this->command->info("   Course → Unit → Lesson → Lesson Block");

        $courses = Course::where('status', 'published')->get();

        if ($courses->isEmpty()) {
            $this->command->warn("  ⚠️  No published courses found. Please run CourseSeeder first.");
            return;
        }

        $this->command->info("\n  📚 Processing {$courses->count()} published courses...");

        $totalUnits = 0;
        $totalLessons = 0;
        $totalBlocks = 0;

        foreach ($courses as $index => $course) {
            $this->command->info("\n  📘 Course " . ($index + 1) . "/{$courses->count()}: {$course->title}");
            
            $unitCount = rand(...self::UNITS_PER_COURSE);
            $units = $this->createUnitsForCourse($course, $unitCount);
            $totalUnits += $units->count();
            $this->command->info("    ✓ Created {$units->count()} units");

            $courseLessons = 0;
            $courseBlocks = 0;

            foreach ($units as $unit) {
                $lessonCount = rand(...self::LESSONS_PER_UNIT);
                $lessons = $this->createLessonsForUnit($unit, $lessonCount);
                $courseLessons += $lessons->count();

                foreach ($lessons as $lesson) {
                    $blockCount = rand(...self::BLOCKS_PER_LESSON);
                    $blocks = $this->createBlocksForLesson($lesson, $blockCount);
                    $courseBlocks += $blocks;
                }
            }

            $totalLessons += $courseLessons;
            $totalBlocks += $courseBlocks;

            $this->command->info("    ✓ Created {$courseLessons} lessons");
            $this->command->info("    ✓ Created {$courseBlocks} lesson blocks");
        }

        $this->command->info("\n✅ Learning content seeding completed!");
        $this->command->info("   📊 Total units: {$totalUnits}");
        $this->command->info("   📊 Total lessons: {$totalLessons}");
        $this->command->info("   📊 Total lesson blocks: {$totalBlocks}");
    }

    private function createUnitsForCourse(Course $course, int $count): \Illuminate\Support\Collection
    {
        $units = collect();

        $unitTitles = [
            'Getting Started',
            'Fundamentals and Core Concepts',
            'Intermediate Techniques',
            'Advanced Topics',
            'Best Practices and Patterns',
            'Real-World Applications',
            'Project Development',
            'Optimization and Performance',
            'Testing and Debugging',
            'Deployment and Maintenance',
            'Security Considerations',
            'Scaling and Architecture',
        ];

        for ($i = 1; $i <= $count; $i++) {
            $title = $unitTitles[$i - 1] ?? "Unit {$i}: " . fake()->words(3, true);
            $code = sprintf('U%d_%d_%s', $course->id, $i, bin2hex(random_bytes(3)));
            $slug = \Illuminate\Support\Str::slug($title) . '-' . $course->id;

            $units->push(Unit::create([
                'course_id' => $course->id,
                'code' => $code,
                'title' => $title,
                'slug' => $slug,
                'description' => $this->generateUnitDescription($title),
                'order' => $i,
                'status' => 'published',
            ]));
        }

        return $units;
    }

    private function createLessonsForUnit(Unit $unit, int $count): \Illuminate\Support\Collection
    {
        $lessons = collect();

        for ($i = 1; $i <= $count; $i++) {
            $title = $this->generateLessonTitle($i);
            $slug = \Illuminate\Support\Str::slug($title) . '-' . $unit->id . '-' . $i;

            $lessons->push(Lesson::create([
                'unit_id' => $unit->id,
                'title' => $title,
                'slug' => $slug,
                'description' => $this->generateLessonDescription(),
                'markdown_content' => $this->generateMarkdownContent(),
                'content_type' => fake()->randomElement(['markdown', 'video', 'link']),
                'content_url' => fake()->optional(0.3)->url(),
                'order' => $i,
                'duration_minutes' => fake()->randomElement([10, 15, 20, 30, 45, 60]),
                'status' => 'published',
            ]));
        }

        return $lessons;
    }

    private function createBlocksForLesson(Lesson $lesson, int $count): int
    {
        $blocks = [];

        $blockTypes = ['text', 'image', 'video', 'file', 'embed'];
        $weights = [0.5, 0.2, 0.15, 0.1, 0.05];

        for ($i = 1; $i <= $count; $i++) {
            $blockType = $this->weightedRandom($blockTypes, $weights);
            
            $blocks[] = [
                'lesson_id' => $lesson->id,
                'block_type' => $blockType,
                'content' => $this->generateBlockContent($blockType),
                'order' => $i,
                'slug' => \Illuminate\Support\Str::slug($lesson->title . '-block-' . $i),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('lesson_blocks')->insert($blocks);
        
        return count($blocks);
    }

    private function generateUnitDescription(string $title): string
    {
        $descriptions = [
            "In this unit, you'll learn the essential concepts and build a solid foundation.",
            "This unit covers practical techniques and hands-on exercises to reinforce your skills.",
            "Dive deeper into advanced topics and explore real-world applications.",
            "Learn industry best practices and professional development workflows.",
            "Master the tools and techniques used by professionals in the field.",
            "Apply your knowledge to build complete projects from scratch.",
        ];

        return fake()->randomElement($descriptions);
    }

    private function generateLessonTitle(int $order): string
    {
        $templates = [
            "Introduction to %s",
            "Understanding %s",
            "Working with %s",
            "Implementing %s",
            "Advanced %s Techniques",
            "Best Practices for %s",
            "%s in Practice",
            "Mastering %s",
            "%s Deep Dive",
            "Building with %s",
        ];

        $topics = [
            'Core Concepts', 'Data Structures', 'Algorithms', 'Design Patterns',
            'APIs', 'Authentication', 'Database Design', 'Testing Strategies',
            'Error Handling', 'Performance Optimization', 'Security Measures',
            'User Interfaces', 'State Management', 'Data Validation',
        ];

        $template = fake()->randomElement($templates);
        $topic = fake()->randomElement($topics);

        return sprintf($template, $topic);
    }

    private function generateLessonDescription(): string
    {
        $descriptions = [
            "Learn key concepts through practical examples and hands-on exercises.",
            "Understand the fundamentals and apply them to real-world scenarios.",
            "Master essential techniques with step-by-step guidance.",
            "Explore advanced topics and industry best practices.",
            "Build practical skills through interactive demonstrations.",
            "Discover professional workflows and efficient development patterns.",
        ];

        return fake()->randomElement($descriptions);
    }

    private function generateMarkdownContent(): string
    {
        $sections = [
            "## Overview\n\nThis lesson covers important concepts that you'll use throughout your learning journey.\n\n",
            "## Key Takeaways\n\n- Master fundamental principles\n- Apply concepts to real projects\n- Understand industry standards\n\n",
            "## Prerequisites\n\nBefore starting, make sure you're familiar with the previous lessons.\n\n",
            "## Learning Objectives\n\n1. Understand core concepts\n2. Implement practical solutions\n3. Apply best practices\n\n",
        ];

        return implode("\n", fake()->randomElements($sections, rand(2, 3)));
    }

    private function generateBlockContent(string $blockType): string
    {
        return match ($blockType) {
            'text' => $this->generateTextBlockContent(),
            'image' => json_encode(['caption' => fake()->sentence(), 'alt' => fake()->words(5, true)]),
            'video' => json_encode(['title' => fake()->sentence(), 'duration' => rand(5, 30) * 60]),
            'code' => json_encode(['language' => fake()->randomElement(['php', 'javascript', 'python']), 'code' => '// Sample code here']),
            'file' => json_encode(['filename' => fake()->word() . '.pdf', 'size' => rand(100, 5000) . 'KB']),
            'embed' => json_encode(['type' => 'youtube', 'embed_code' => '<iframe>...</iframe>']),
            default => fake()->paragraph(),
        };
    }

    private function generateTextBlockContent(): string
    {
        $paragraphs = [
            "This concept is fundamental to understanding how the system works. By mastering this, you'll be able to apply it across various scenarios.",
            "Let's explore this topic in detail. We'll look at practical examples and see how professionals use these techniques in production environments.",
            "Understanding this principle will help you make better design decisions and write more maintainable code.",
            "In practice, this technique is widely used across the industry. Many successful projects rely on this approach.",
        ];

        return implode("\n\n", fake()->randomElements($paragraphs, rand(2, 3)));
    }

    private function generateMediaUrl(?string $blockType): ?string
    {
        if ($blockType === 'video') {
            return 'https://www.youtube.com/watch?v=' . \Illuminate\Support\Str::random(11);
        }
        
        if ($blockType === 'image') {
            return 'https://placehold.co/800x600/png?text=' . urlencode(fake()->words(2, true));
        }

        if ($blockType === 'file') {
            return fake()->url() . '/files/' . fake()->word() . '.pdf';
        }

        return null;
    }

    private function generateThumbnailUrl(?string $blockType): ?string
    {
        if (in_array($blockType, ['video', 'image'])) {
            return 'https://placehold.co/400x300/png?text=Thumbnail';
        }

        return null;
    }

    private function generateMediaMeta(?string $blockType): ?string
    {
        if ($blockType === 'video') {
            return json_encode([
                'duration' => rand(300, 3600),
                'resolution' => '1080p',
                'provider' => 'youtube',
            ]);
        }

        if ($blockType === 'image') {
            return json_encode([
                'width' => 1920,
                'height' => 1080,
                'format' => 'jpeg',
            ]);
        }

        return null;
    }

    private function weightedRandom(array $values, array $weights): mixed
    {
        $totalWeight = array_sum($weights);
        $random = mt_rand(1, (int) ($totalWeight * 100)) / 100;

        $sum = 0;
        foreach ($values as $index => $value) {
            $sum += $weights[$index];
            if ($random <= $sum) {
                return $value;
            }
        }

        return $values[0];
    }
}
