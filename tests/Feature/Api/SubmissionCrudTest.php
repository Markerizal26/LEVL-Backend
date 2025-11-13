<?php

use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    createTestRoles();
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->student = User::factory()->create();
    $this->student->assignRole('student');
    $this->course = Course::factory()->create(['instructor_id' => $this->admin->id]);
    $this->unit = Unit::factory()->create(['course_id' => $this->course->id]);
    $this->lesson = Lesson::factory()->create(['unit_id' => $this->unit->id]);
    $this->assignment = Assignment::create([
        'lesson_id' => $this->lesson->id,
        'created_by' => $this->admin->id,
        'title' => 'Test Assignment',
        'submission_type' => 'text',
        'status' => 'published',
    ]);
    $this->enrollment = Enrollment::factory()->create([
        'user_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
    ]);
});

// ==================== POSITIVE TEST CASES ====================

// POST - Create Submission
it('student can create submission for published assignment', function () {
    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/assignments/{$this->assignment->id}/submissions"), [
            'answer_text' => 'This is my answer',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['submission']])
        ->assertJsonPath('data.submission.status', 'submitted');

    assertDatabaseHas('submissions', [
        'assignment_id' => $this->assignment->id,
        'user_id' => $this->student->id,
        'status' => 'submitted',
    ]);
});

// PUT - Update Submission
it('student can update their own draft submission', function () {
    $submission = Submission::create([
        'assignment_id' => $this->assignment->id,
        'user_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'answer_text' => 'Original answer',
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->putJson(api("/submissions/{$submission->id}"), [
            'answer_text' => 'Updated answer',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.submission.answer_text', 'Updated answer');
});

// POST - Grade Submission
it('admin can grade submission', function () {
    $submission = Submission::create([
        'assignment_id' => $this->assignment->id,
        'user_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'answer_text' => 'Student answer',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/submissions/{$submission->id}/grade"), [
            'score' => 85,
            'feedback' => 'Good work!',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.submission.status', 'graded')
        ->assertJsonPath('data.submission.score', 85);

    assertDatabaseHas('submissions', [
        'id' => $submission->id,
        'status' => 'graded',
    ]);

    assertDatabaseHas('grades', [
        'source_type' => 'assignment',
        'source_id' => $this->assignment->id,
        'user_id' => $this->student->id,
        'score' => 85,
        'feedback' => 'Good work!',
    ]);
});

// ==================== NEGATIVE TEST CASES ====================

// POST - Create Submission Negative
it('cannot create submission for draft assignment', function () {
    $draftAssignment = Assignment::create([
        'lesson_id' => $this->lesson->id,
        'created_by' => $this->admin->id,
        'title' => 'Draft Assignment',
        'submission_type' => 'text',
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/assignments/{$draftAssignment->id}/submissions"), [
            'answer_text' => 'My answer',
        ]);

    $response->assertStatus(422);
});

it('cannot create submission without enrollment', function () {
    $otherCourse = Course::factory()->create();
    $otherUnit = Unit::factory()->create(['course_id' => $otherCourse->id]);
    $otherLesson = Lesson::factory()->create(['unit_id' => $otherUnit->id]);
    $otherAssignment = Assignment::create([
        'lesson_id' => $otherLesson->id,
        'created_by' => $this->admin->id,
        'title' => 'Other Assignment',
        'submission_type' => 'text',
        'status' => 'published',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/assignments/{$otherAssignment->id}/submissions"), [
            'answer_text' => 'My answer',
        ]);

    $response->assertStatus(422);
});

it('unauthenticated user cannot create submission', function () {
    $response = $this->postJson(api("/assignments/{$this->assignment->id}/submissions"), [
        'answer_text' => 'My answer',
    ]);

    $response->assertStatus(401);
});

// PUT - Update Submission Negative
it('cannot update graded submission', function () {
    $submission = Submission::create([
        'assignment_id' => $this->assignment->id,
        'user_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'answer_text' => 'Original answer',
        'status' => 'graded',
    ]);
    
    \Modules\Grading\Models\Grade::create([
        'source_type' => 'assignment',
        'source_id' => $this->assignment->id,
        'user_id' => $this->student->id,
        'score' => 80,
        'max_score' => 100,
        'status' => 'graded',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->putJson(api("/submissions/{$submission->id}"), [
            'answer_text' => 'Updated answer',
        ]);

    $response->assertStatus(422);
});

it('cannot update other user submission', function () {
    $otherStudent = User::factory()->create();
    $otherStudent->assignRole('student');
    $submission = Submission::create([
        'assignment_id' => $this->assignment->id,
        'user_id' => $otherStudent->id,
        'enrollment_id' => $this->enrollment->id,
        'answer_text' => 'Other answer',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->putJson(api("/submissions/{$submission->id}"), [
            'answer_text' => 'Updated answer',
        ]);

    $response->assertStatus(403);
});

it('cannot update non-existent submission', function () {
    $response = $this->actingAs($this->student, 'api')
        ->putJson(api('/submissions/99999'), [
            'answer_text' => 'Non Existent',
        ]);

    $response->assertStatus(404);
});

it('cannot update submitted submission', function () {
    $submission = Submission::create([
        'assignment_id' => $this->assignment->id,
        'user_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'answer_text' => 'Submitted answer',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->putJson(api("/submissions/{$submission->id}"), [
            'answer_text' => 'Updated answer',
        ]);

    $response->assertStatus(422);
});

// POST - Grade Submission Negative
it('student cannot grade submission', function () {
    $submission = Submission::create([
        'assignment_id' => $this->assignment->id,
        'user_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'answer_text' => 'Student answer',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($this->student, 'api')
        ->postJson(api("/submissions/{$submission->id}/grade"), [
            'score' => 100,
            'feedback' => 'Self grade',
        ]);

    $response->assertStatus(403);
});

it('cannot grade with invalid score', function () {
    $submission = Submission::create([
        'assignment_id' => $this->assignment->id,
        'user_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'answer_text' => 'Student answer',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api("/submissions/{$submission->id}/grade"), [
            'score' => 150, // Exceeds max_score
            'feedback' => 'Too high',
        ]);

    $response->assertStatus(422);
});

it('cannot grade non-existent submission', function () {
    $response = $this->actingAs($this->admin, 'api')
        ->postJson(api('/submissions/99999/grade'), [
            'score' => 85,
            'feedback' => 'Non Existent',
        ]);

    $response->assertStatus(404);
});

