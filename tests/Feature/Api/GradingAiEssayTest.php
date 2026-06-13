<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Unit;
use Tests\ApiTestCase;

final class GradingAiEssayTest extends ApiTestCase
{
    private function url(string $uri): string
    {
        return '/api/v1'.$uri;
    }

    private function makeEssaySubmission(User $creator, string $answerText = 'Student essay answer.'): Submission
    {
        $course = Course::factory()->published()->openEnrollment()->create();
        $unit = Unit::factory()->forCourse($course)->create();

        $assignment = Assignment::create([
            'unit_id' => $unit->id,
            'order' => 1,
            'created_by' => $creator->id,
            'title' => 'Essay Assignment',
            'description' => 'Explain dependency injection.',
            'submission_type' => 'text',
            'max_score' => 50,
            'review_mode' => 'manual',
            'status' => 'published',
        ]);

        $student = User::factory()->active()->create([
            'email' => 'essay-student-'.Str::uuid().'@example.test',
            'username' => 'essay_student_'.Str::lower(Str::random(12)),
        ]);

        $enrollment = Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
            'enrolled_at' => now(),
            'completed_at' => null,
        ]);

        return Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'answer_text' => $answerText,
            'status' => 'submitted',
            'state' => 'pending_manual_grading',
            'attempt_number' => 1,
            'submitted_at' => now(),
        ]);
    }

    public function test_batch_essays_returns_text_and_handles_missing_without_500(): void
    {
        $admin = $this->actingAsAdmin();
        $submission = $this->makeEssaySubmission($admin, 'My structured answer.');

        $response = $this->postJson($this->url('/grading/essays/batch'), [
            'items' => [
                ['submission_id' => $submission->id, 'type' => 'assignment'],
                ['submission_id' => 999999, 'type' => 'assignment'],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);
        $this->assertTrue($data[0]['found']);
        $this->assertSame('Explain dependency injection.', $data[0]['question_text']);
        $this->assertSame('My structured answer.', $data[0]['student_answer']);
        $this->assertEquals(50, $data[0]['max_score']);
        $this->assertFalse($data[1]['found']);
    }

    public function test_batch_essays_validates_payload(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson($this->url('/grading/essays/batch'), ['items' => []]);

        $response->assertStatus(422);
    }

    public function test_save_draft_persists_ai_fields_and_returns_in_get(): void
    {
        $admin = $this->actingAsAdmin();
        $submission = $this->makeEssaySubmission($admin);

        $draft = $this->putJson($this->url('/submissions/'.$submission->id.'/grades/draft'), [
            'score' => 42,
            'feedback' => 'Good attempt.',
            'is_ai_assisted' => true,
            'ai_suggested_score' => 40,
        ]);

        $draft->assertStatus(200);

        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'is_ai_assisted' => true,
            'ai_suggested_score' => 40,
        ]);

        $get = $this->getJson($this->url('/submissions/'.$submission->id.'/grades'));
        $get->assertStatus(200);
        $get->assertJsonPath('data.is_ai_assisted', true);
        $this->assertEquals(40, $get->json('data.ai_suggested_score'));
    }

    public function test_assignment_detail_returns_question_text(): void
    {
        $admin = $this->actingAsAdmin();
        $submission = $this->makeEssaySubmission($admin);

        $response = $this->getJson($this->url('/grading/'.$submission->id.'?type=assignment'));

        $response->assertStatus(200);
        $response->assertJsonPath('data.question_text', 'Explain dependency injection.');
    }
}
