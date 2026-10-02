<?php

declare(strict_types=1);

namespace Modules\Schemes\Database\Seeders;

use App\Support\RealisticSeederContent;
use App\Support\UATMediaFixtures;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Modules\Schemes\Enums\BlockType;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\LessonBlock;
use Modules\Schemes\Models\Unit;

class LearningContentSeeder extends Seeder
{
    private const UNITS_PER_COURSE = [2, 4];

    private const LESSONS_PER_UNIT = [3, 6];

    private const BLOCKS_PER_LESSON = [3, 5];

    private array $dummyFiles = [];

    public function run(): void
    {
        $this->command->info("\n📖 Creating learning content hierarchy with media...");
        $this->command->info('   Course → Unit → Lesson → Lesson Block (with files)');

        try {
            $disk = \Storage::disk(config('media-library.disk_name', 'public'));
            $testFile = 'test-connection-'.time().'.txt';
            $disk->put($testFile, 'test');
            $disk->delete($testFile);
            $this->command->info('  ✓ Local VPS storage is writable');
        } catch (\Exception $e) {
            $this->command->error('  ❌ Local VPS storage check failed: '.$e->getMessage());
            $this->command->warn('  ⚠️  Media upload will be skipped. Please check the storage volume permissions.');

            return;
        }

        $this->checkDummyFiles();

        if (Course::has('units')->exists()) {
            $this->command->info('  ℹ️  Learning content already seeded. Skipping to preserve idempotency.');

            return;
        }

        $courses = Course::all();

        if ($courses->isEmpty()) {
            $this->command->warn('  ⚠️  No courses found. Please run CourseSeeder first.');

            return;
        }

        $this->command->info("\n  📚 Processing {$courses->count()} courses...");

        $totalUnits = 0;
        $totalLessons = 0;
        $totalBlocks = 0;
        $totalMedia = 0;

        foreach ($courses as $index => $course) {
            $this->command->info("\n  📘 Course ".($index + 1)."/{$courses->count()}: {$course->title}");

            $unitCount = self::UNITS_PER_COURSE[0] + (($course->id + $index) % (self::UNITS_PER_COURSE[1] - self::UNITS_PER_COURSE[0] + 1));
            $units = $this->createUnitsForCourse($course, $unitCount);
            $totalUnits += $units->count();
            $this->command->info("    ✓ Created {$units->count()} units");

            $courseLessons = 0;
            $courseBlocks = 0;
            $courseMedia = 0;

            foreach ($units as $unit) {
                $lessonCount = self::LESSONS_PER_UNIT[0] + ($unit->id % (self::LESSONS_PER_UNIT[1] - self::LESSONS_PER_UNIT[0] + 1));
                $lessons = $this->createLessonsForUnit($unit, $lessonCount);
                $courseLessons += $lessons->count();

                foreach ($lessons as $lesson) {
                    $result = $this->createBlocksForLesson($lesson);
                    $courseBlocks += $result['blocks'];
                    $courseMedia += $result['media'];
                }
            }

            $totalLessons += $courseLessons;
            $totalBlocks += $courseBlocks;
            $totalMedia += $courseMedia;

            $this->command->info("    ✓ Created {$courseLessons} lessons");
            $this->command->info("    ✓ Created {$courseBlocks} lesson blocks");
            $this->command->info("    ✓ Uploaded {$courseMedia} media files");
        }

        $this->command->info("\n✅ Learning content seeding completed!");
        $this->command->info("   📊 Total units: {$totalUnits}");
        $this->command->info("   📊 Total lessons: {$totalLessons}");
        $this->command->info("   📊 Total lesson blocks: {$totalBlocks}");
        $this->command->info("   📊 Total media files: {$totalMedia}");
    }

    private function checkDummyFiles(): void
    {

        UATMediaFixtures::ensureFilesExist();

        $this->dummyFiles = UATMediaFixtures::paths();

        $this->command->info('  📁 Checking dummy file paths:');
        foreach ($this->dummyFiles as $type => $path) {
            $exists = File::exists($path) ? '✓' : '✗';
            $this->command->info("     {$exists} {$type}: {$path}");
        }

        $fallbackBase = public_path('dummy');
        $fallback = [
            'video' => $fallbackBase.'/file_example_MP4_480_1_5MG.mp4',
            'image' => $fallbackBase.'/file_example_PNG_500kB (1).png',
            'excel' => $fallbackBase.'/file_example_XLS_5000.xls',
            'doc' => $fallbackBase.'/file-sample_500kB.doc',
            'pdf' => $fallbackBase.'/pdf-sample_0.pdf',
        ];

        $missing = [];
        foreach ($this->dummyFiles as $type => $path) {
            if (! File::exists($path)) {
                $missing[] = $type;
                if (File::exists($fallback[$type] ?? '')) {
                    $this->dummyFiles[$type] = $fallback[$type];
                    $this->command->info("     → Using fallback for {$type}: {$fallback[$type]}");
                }
            }
        }

        if (! empty($missing)) {
            $this->command->warn('  ⚠️  Missing UAT fixture files (used fallback where possible): '.implode(', ', $missing));
        } else {
            $this->command->info('  ✓ All UAT fixture files are ready');
        }
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
            $title = $unitTitles[$i - 1] ?? "Unit {$i}: ".RealisticSeederContent::wordToken($i).' '.RealisticSeederContent::wordToken($i + 3);
            $code = sprintf('U%d_%d_%s', $course->id, $i, substr(md5($course->id.'-unit-'.$i), 0, 6));
            $slug = \Illuminate\Support\Str::slug($title).'-'.$course->id.'-'.substr(md5($title.$course->id), 0, 6);

            $units->push(Unit::create([
                'course_id' => $course->id,
                'code' => $code,
                'title' => $title,
                'slug' => $slug,
                'description' => $this->generateUnitDescription($title, $i),
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
            $slug = \Illuminate\Support\Str::slug($title).'-'.$unit->id.'-'.$i;

            $lesson = Lesson::create([
                'unit_id' => $unit->id,
                'title' => $title,
                'slug' => $slug,
                'description' => $this->generateLessonDescription($i),
                'markdown_content' => $this->generateMarkdownContent($unit->id * 100 + $i),
                'content_type' => ['markdown', 'video', 'link'][$i % 3],
                'content_url' => ($i % 10 < 3) ? 'https://demo.levl.id/materi/'.$unit->id.'/'.$i : null,
                'order' => $i,
                'duration_minutes' => [10, 15, 20, 30, 45, 60][$i % 6],
                'status' => 'published',
            ]);

            \Illuminate\Support\Facades\DB::table('unit_contents')->insert([
                'unit_id' => $unit->id,
                'contentable_type' => 'lesson',
                'contentable_id' => $lesson->id,
                'order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $lessons->push($lesson);
        }

        return $lessons;
    }

    private function createBlocksForLesson(Lesson $lesson): array
    {
        $count = self::BLOCKS_PER_LESSON[0] + ($lesson->id % (self::BLOCKS_PER_LESSON[1] - self::BLOCKS_PER_LESSON[0] + 1));

        $blockTypes = BlockType::values();

        $blocksCreated = 0;
        $mediaUploaded = 0;
        $blockTypeCount = [];

        for ($i = 0; $i < $count; $i++) {

            $blockType = $blockTypes[$i % count($blockTypes)];
            $seed = $lesson->id * 50 + $i;
            $externalUrl = $this->generateExternalUrl($blockType, $seed);

            if (! isset($blockTypeCount[$blockType])) {
                $blockTypeCount[$blockType] = 0;
            }
            $blockTypeCount[$blockType]++;

            $block = LessonBlock::create([
                'lesson_id' => $lesson->id,
                'block_type' => $blockType,
                'content' => $this->generateBlockContent($blockType, $seed),
                'external_url' => $externalUrl,
                'order' => $i + 1,
                'slug' => \Illuminate\Support\Str::slug($lesson->title.'-block-'.($i + 1)),
            ]);

            $blocksCreated++;

            if (in_array($blockType, [BlockType::Video->value, BlockType::File->value, BlockType::Image->value], true)) {
                if ($this->attachMediaToBlock($block, $blockType)) {
                    $mediaUploaded++;
                }
            }
        }

        if ($lesson->id <= 3) {
            $this->command->info("    📊 Block types for lesson {$lesson->id}: ".json_encode($blockTypeCount));
        }

        return ['blocks' => $blocksCreated, 'media' => $mediaUploaded];
    }

    private function attachMediaToBlock(LessonBlock $block, string $blockType): bool
    {

        static $attemptCount = 0;
        $attemptCount++;

        if ($attemptCount <= 5) {
            $this->command->info("    🔍 Attempt #{$attemptCount}: Attaching {$blockType} media to block {$block->id}");
        }

        try {
            switch ($blockType) {
                case 'video':
                    if (! isset($this->dummyFiles['video'])) {
                        $this->command->warn('    ⚠️  Video file path not set in dummyFiles');

                        return false;
                    }
                    if (! File::exists($this->dummyFiles['video'])) {
                        $this->command->warn("    ⚠️  Video file not found: {$this->dummyFiles['video']}");

                        return false;
                    }
                    $block->addMedia($this->dummyFiles['video'])
                        ->preservingOriginal()
                        ->toMediaCollection('media', config('media-library.disk_name', 'public'));

                    if ($attemptCount <= 5) {
                        $this->command->info("    ✅ Successfully uploaded video for block {$block->id}");
                    }

                    return true;

                case 'file':
                    $fileType = ['pdf', 'doc', 'excel'][$block->id % 3];
                    $filePath = match ($fileType) {
                        'pdf' => $this->dummyFiles['pdf'] ?? null,
                        'doc' => $this->dummyFiles['doc'] ?? null,
                        'excel' => $this->dummyFiles['excel'] ?? null,
                        default => null,
                    };

                    if (! $filePath) {
                        $this->command->warn("    ⚠️  File path not set for type: {$fileType}");

                        return false;
                    }
                    if (! File::exists($filePath)) {
                        $this->command->warn("    ⚠️  File not found: {$filePath}");

                        return false;
                    }
                    $block->addMedia($filePath)
                        ->preservingOriginal()
                        ->toMediaCollection('media', config('media-library.disk_name', 'public'));

                    if ($attemptCount <= 5) {
                        $this->command->info("    ✅ Successfully uploaded {$fileType} for block {$block->id}");
                    }

                    return true;

                case 'image':
                    if (! isset($this->dummyFiles['image'])) {
                        $this->command->warn('    ⚠️  Image file path not set in dummyFiles');

                        return false;
                    }
                    if (! File::exists($this->dummyFiles['image'])) {
                        $this->command->warn("    ⚠️  Image file not found: {$this->dummyFiles['image']}");

                        return false;
                    }
                    $block->addMedia($this->dummyFiles['image'])
                        ->preservingOriginal()
                        ->toMediaCollection('media', config('media-library.disk_name', 'public'));

                    if ($attemptCount <= 5) {
                        $this->command->info("    ✅ Successfully uploaded image for block {$block->id}");
                    }

                    return true;
            }
        } catch (\Throwable $e) {

            $this->command->error("    ❌ Failed to attach media to block {$block->id} ({$blockType}): {$e->getMessage()}");
            if ($attemptCount <= 3) {
                $this->command->error('    Error type: '.get_class($e));
            }

            return false;
        }

        return false;
    }

    private function generateUnitDescription(string $title, int $i): string
    {
        $descriptions = [
            "Unit ini membahas fondasi kompetensi sesuai skema: {$title}.",
            "Peserta mempraktikkan prosedur standar dan merujuk pada pedoman asesmen untuk {$title}.",
            "Materi mengaitkan teori dengan studi kasus industri terkait {$title}.",
            'Aktivitas dirancang agar capaian dapat diverifikasi melalui tugas terstruktur.',
            'Ringkasan kompetensi dan kriteria penilaian disampaikan di awal unit.',
            'Latihan mandiri melengkapi pembahasan kelas untuk penguasaan bertahap.',
        ];

        return $descriptions[$i % count($descriptions)];
    }

    private function generateLessonTitle(int $order): string
    {
        $templates = [
            'Pengantar %s',
            'Memahami %s',
            'Penerapan %s dalam Praktik',
            'Langkah Kerja %s',
            'Teknik Lanjutan untuk %s',
            'Studi Kasus %s',
            '%s untuk Uji Kompetensi',
            'Checklist %s',
        ];

        $topics = [
            'Unit Kompetensi', 'Prosedur Kerja', 'Instrumen Asesmen', 'Kriteria Bukti',
            'K3 Lingkungan', 'Dokumentasi', 'Komunikasi Profesional', 'Etika Profesi',
            'Perencanaan Aktivitas', 'Evaluasi Diri', 'Kepatuhan Standar', 'Kolaborasi Tim',
        ];

        $template = $templates[$order % count($templates)];
        $topic = $topics[$order % count($topics)];

        return sprintf($template, $topic);
    }

    private function generateLessonDescription(int $order): string
    {
        $descriptions = [
            'Materi disusun mengikuti urutan pembelajaran dari konsep dasar hingga penerapan.',
            'Setiap bagian dilengkapi dengan contoh yang relevan dengan konteks LSP.',
            'Peserta mengumpulkan bukti sesuai instruksi asesor di akhir sesi.',
            'Fokus pada prosedur yang dapat direplikasi di tempat kerja.',
            'Latihan membantu peserta memantau penguasaan indikator kinerja.',
            'Ringkasan menegaskan poin yang sering diuji pada asesmen.',
        ];

        return $descriptions[$order % count($descriptions)];
    }

    private function generateMarkdownContent(int $seed): string
    {
        $n = 2 + ($seed % 2);

        return implode("\n", array_map(
            fn (int $j) => RealisticSeederContent::lessonMarkdownSection($seed + $j),
            range(0, $n - 1)
        ));
    }

    private function generateBlockContent(string $blockType, int $seed): string
    {
        return match ($blockType) {
            'text' => $this->generateTextBlockContent($seed),
            'image' => '<figure><img src="" alt="'.htmlspecialchars(RealisticSeederContent::assessmentSentence($seed), ENT_QUOTES, 'UTF-8').'" /><figcaption>'.htmlspecialchars(RealisticSeederContent::shortSentence($seed + 1), ENT_QUOTES, 'UTF-8').'</figcaption></figure>',
            'video' => '<div class="video-wrapper"><video controls><source src="" type="video/mp4" /></video><p class="video-description">'.htmlspecialchars(RealisticSeederContent::paragraph($seed), ENT_QUOTES, 'UTF-8').'</p></div>',
            'file' => '<div class="file-download"><h4>'.htmlspecialchars(RealisticSeederContent::shortSentence($seed + 2), ENT_QUOTES, 'UTF-8').'</h4><p>'.htmlspecialchars(RealisticSeederContent::paragraph($seed + 3), ENT_QUOTES, 'UTF-8').'</p><a href="" download>Unduh berkas</a></div>',
            'youtube' => '<p>'.htmlspecialchars(RealisticSeederContent::shortSentence($seed + 5), ENT_QUOTES, 'UTF-8').'</p>',
            'drive' => '<p>'.htmlspecialchars(RealisticSeederContent::shortSentence($seed + 6), ENT_QUOTES, 'UTF-8').'</p>',
            'link' => '<p>'.htmlspecialchars(RealisticSeederContent::shortSentence($seed + 7), ENT_QUOTES, 'UTF-8').'</p>',
            'embed' => '<p>'.htmlspecialchars(RealisticSeederContent::shortSentence($seed + 8), ENT_QUOTES, 'UTF-8').'</p>',
            default => RealisticSeederContent::paragraph($seed + 4),
        };
    }

    private function generateExternalUrl(string $blockType, int $seed): ?string
    {
        return match ($blockType) {
            'youtube' => 'https://www.youtube.com/watch?v='.substr(md5('yt-'.$seed), 0, 11),
            'drive' => 'https://drive.google.com/file/d/'.substr(md5('drive-'.$seed), 0, 28).'/view',
            'link' => 'https://docs.levl.id/materi/'.($seed % 200 + 1),
            'embed' => 'https://www.youtube.com/embed/'.substr(md5('embed-'.$seed), 0, 11),
            default => null,
        };
    }

    private function generateTextBlockContent(int $seed): string
    {
        return RealisticSeederContent::paragraph($seed)."\n\n".RealisticSeederContent::paragraph($seed + 1);
    }

    private function pickWeightedIndex(int $seed, array $weights): int
    {
        $totalWeight = array_sum($weights);
        $r = ($seed % 10000) / 10000 * $totalWeight;
        $sum = 0.0;
        foreach ($weights as $index => $w) {
            $sum += $w;
            if ($r <= $sum) {
                return $index;
            }
        }

        return 0;
    }
}
