<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\RealisticSeederContent;
use App\Support\SeederDate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Grading\Models\Grade;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;
use Modules\Schemes\Models\Course;

class UATSystemAnalystGradingSeeder extends Seeder
{
    private const COURSE_SLUG = 'pelatihan-system-analyst';

    private const EMAIL_PREFIX = 'uat.sa.asesi.';

    /**
     * Antrean penilaian dibaca dari submissions.state (bukan dari tabel grades),
     * jadi setiap skenario di bawah menetapkan state secara eksplisit.
     */
    private const PERSONAS = [
        ['n' => 1, 'scenario' => 'antre'],
        ['n' => 2, 'scenario' => 'antre'],
        ['n' => 3, 'scenario' => 'antre'],
        ['n' => 4, 'scenario' => 'antre'],
        ['n' => 5, 'scenario' => 'antre_terlambat'],
        ['n' => 6, 'scenario' => 'draft_asesi'],
        ['n' => 7, 'scenario' => 'draft_nilai', 'score' => 78.0],
        ['n' => 8, 'scenario' => 'draft_nilai', 'score' => 64.0],
        ['n' => 9, 'scenario' => 'draft_nilai_ai', 'score' => 81.0, 'ai_score' => 79.0],
        ['n' => 10, 'scenario' => 'dinilai_belum_rilis', 'score' => 85.0],
        ['n' => 11, 'scenario' => 'dinilai_belum_rilis', 'score' => 72.0],
        ['n' => 12, 'scenario' => 'dinilai_belum_rilis', 'score' => 90.0],
        ['n' => 13, 'scenario' => 'dirilis_lulus', 'score' => 88.0],
        ['n' => 14, 'scenario' => 'dirilis_tidak_lulus', 'score' => 62.0],
    ];

    public function run(): void
    {
        if (config('seeding.mode') !== 'uat') {
            $this->command->warn('UAT System Analyst grading: dilewati, seeding.mode bukan uat.');

            return;
        }

        $course = Course::query()->where('slug', self::COURSE_SLUG)->first();
        if ($course === null) {
            $this->command->warn('UAT System Analyst grading: dilewati, course '.self::COURSE_SLUG.' tidak ditemukan.');

            return;
        }

        $assignment = Assignment::query()
            ->whereHas('unit', fn ($q) => $q->where('course_id', $course->id))
            ->where('status', 'published')
            ->orderBy('id')
            ->first();

        if ($assignment === null) {
            $this->command->warn('UAT System Analyst grading: dilewati, tidak ada assignment published di course '.$course->id.'.');

            return;
        }

        $grader = $course->instructors()->orderBy('users.id')->first()
            ?? User::query()->find($course->instructor_id);

        if ($grader === null) {
            $this->command->warn('UAT System Analyst grading: dilewati, course belum punya instruktur (course_admins kosong).');

            return;
        }

        $this->command->info('UAT System Analyst grading: course='.$course->id.' assignment='.$assignment->id.' grader='.$grader->id);

        DB::transaction(function () use ($course, $assignment, $grader) {
            $created = 0;

            foreach (self::PERSONAS as $persona) {
                $user = $this->makeAsesi($persona['n']);
                $enrollment = $this->makeEnrollment($user, $course);
                $submission = $this->makeSubmission($persona, $user, $enrollment, $assignment);

                $this->makeGrade($persona, $submission, $assignment, $grader);
                $created++;
            }

            $this->command->info('UAT System Analyst grading: '.$created.' asesi + submission siap.');
        });

        $this->report($assignment->id);
    }

    private function makeAsesi(int $n): User
    {
        $idx = 9000 + $n;
        [$first, $last] = RealisticSeederContent::indonesianNamePair($idx);
        $email = RealisticSeederContent::demoEmail(self::EMAIL_PREFIX.str_pad((string) $n, 2, '0', STR_PAD_LEFT));

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $first.' '.$last,
                'username' => 'uat_sa_asesi_'.str_pad((string) $n, 2, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => SeederDate::randomPastDateTimeBetween(20, 40),
            ]
        );

        if (! $user->hasRole('Student')) {
            $user->assignRole('Student');
        }

        return $user;
    }

    private function makeEnrollment(User $user, Course $course): Enrollment
    {
        return Enrollment::query()->firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            [
                'status' => 'active',
                'enrolled_at' => SeederDate::randomPastCarbonBetween(15, 30),
            ]
        );
    }

    private function makeSubmission(array $persona, User $user, Enrollment $enrollment, Assignment $assignment): Submission
    {
        $scenario = $persona['scenario'];

        [$status, $state] = match ($scenario) {
            'draft_asesi' => ['draft', 'in_progress'],
            'antre', 'antre_terlambat', 'draft_nilai', 'draft_nilai_ai' => ['submitted', 'pending_manual_grading'],
            'dinilai_belum_rilis' => ['graded', 'graded'],
            'dirilis_lulus', 'dirilis_tidak_lulus' => ['graded', 'released'],
        };

        $isScored = in_array($scenario, ['dinilai_belum_rilis', 'dirilis_lulus', 'dirilis_tidak_lulus'], true);
        $submittedAt = $scenario === 'draft_asesi'
            ? null
            : SeederDate::randomPastCarbonBetween(2, 12);

        $submission = Submission::query()->updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
                'attempt_number' => 1,
            ],
            [
                'enrollment_id' => $enrollment->id,
                'answer_text' => $this->answerText($persona['n']),
                'status' => $status,
                'state' => $state,
                'score' => $isScored ? $persona['score'] : null,
                'submitted_at' => $submittedAt,
            ]
        );

        $submission->forceFill(['is_late' => $scenario === 'antre_terlambat' ? '1' : '0'])->save();

        return $submission;
    }

    private function makeGrade(array $persona, Submission $submission, Assignment $assignment, User $grader): void
    {
        $scenario = $persona['scenario'];

        if (in_array($scenario, ['antre', 'antre_terlambat', 'draft_asesi'], true)) {
            return;
        }

        $isDraft = in_array($scenario, ['draft_nilai', 'draft_nilai_ai'], true);
        $gradedAt = $isDraft ? null : SeederDate::randomPastCarbonBetween(1, 5);
        $releasedAt = in_array($scenario, ['dirilis_lulus', 'dirilis_tidak_lulus'], true)
            ? $gradedAt->copy()->addHours(6)
            : null;

        $grade = Grade::query()->updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'source_type' => 'assignment',
                'source_id' => $assignment->id,
                'user_id' => $submission->user_id,
                'graded_by' => $grader->id,
                'score' => $persona['score'],
                'max_score' => $assignment->max_score,
                'feedback' => RealisticSeederContent::assignmentFeedback($persona['n']),
                'status' => $isDraft ? 'pending' : 'graded',
                'is_draft' => $isDraft,
                'graded_at' => $gradedAt,
                'released_at' => $releasedAt,
            ]
        );

        if ($scenario === 'draft_nilai_ai') {
            $grade->forceFill([
                'is_ai_assisted' => true,
                'ai_suggested_score' => $persona['ai_score'],
                'ai_reasoning' => 'Jawaban mengidentifikasi peran System Analyst sebagai penghubung stakeholder dan tim teknis, '
                    .'serta menyebut aktivitas elisitasi kebutuhan dan penyusunan spesifikasi. Struktur argumen runtut, '
                    .'namun contoh penerapan pada studi kasus masih dangkal sehingga skor tidak penuh.',
            ])->save();
        }
    }

    private function answerText(int $n): string
    {
        $answers = [
            'System Analyst berperan sebagai jembatan antara kebutuhan bisnis dan tim pengembang. Pada studi kasus ini, '
                .'analis mengidentifikasi masalah pencatatan stok yang masih manual, lalu menyusun kebutuhan fungsional '
                .'berupa modul input dan pelaporan stok otomatis.',
            'Tanggung jawab utama System Analyst mencakup elisitasi kebutuhan melalui wawancara dan observasi, '
                .'dokumentasi kebutuhan dalam bentuk SRS, serta validasi solusi bersama stakeholder sebelum masuk tahap '
                .'perancangan sistem.',
            'Dalam kasus yang dianalisis, System Analyst melakukan pemetaan proses bisnis berjalan menggunakan DFD, '
                .'menemukan adanya duplikasi entri data antar bagian, dan mengusulkan integrasi basis data agar satu '
                .'sumber data dipakai bersama.',
            'System Analyst bertugas menerjemahkan kebutuhan pengguna menjadi spesifikasi teknis yang dapat dieksekusi. '
                .'Komunikasi dengan stakeholder dilakukan berkala agar ruang lingkup tetap terkendali dan tidak terjadi '
                .'scope creep.',
        ];

        return '<p class="text-node">'.$answers[$n % count($answers)].'</p>';
    }

    private function report(int $assignmentId): void
    {
        $rows = DB::table('submissions')
            ->leftJoin('grades', 'grades.submission_id', '=', 'submissions.id')
            ->where('submissions.assignment_id', $assignmentId)
            ->selectRaw('submissions.state, count(*) as n, count(grades.id) as with_grade')
            ->groupBy('submissions.state')
            ->get();

        $this->command->info('UAT System Analyst grading: distribusi antrean assignment '.$assignmentId);
        foreach ($rows as $row) {
            $this->command->info('  state='.str_pad((string) $row->state, 24).' submission='.$row->n.' punya_grade='.$row->with_grade);
        }
    }
}
