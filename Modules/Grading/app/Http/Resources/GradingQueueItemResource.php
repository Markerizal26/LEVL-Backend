<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Learning\Models\QuizSubmission;

class GradingQueueItemResource extends JsonResource
{
    public bool $isDetailView = false;

    public function __construct(mixed $resource, bool $isDetailView = false)
    {
        parent::__construct($resource);
        $this->isDetailView = $isDetailView;
    }

    public function toArray(Request $request): array
    {
        if (is_array($this->resource) && isset($this->resource['quiz_submission'], $this->resource['essay_answer'])) {
            return $this->toQuizEssayRowArray($this->resource['quiz_submission'], $this->resource['essay_answer']);
        }

        if ($this->resource instanceof QuizSubmission) {
            return $this->toQuizArray();
        }

        return $this->toAssignmentArray();
    }

    private function toAssignmentArray(): array
    {
        $statusValue = $this->status instanceof \BackedEnum ? $this->status->value : $this->status;
        $workflowValue = $this->state instanceof \BackedEnum ? $this->state->value : $this->state;
        $submissionType = $this->assignment?->submission_type?->value ?? $this->assignment?->submission_type;
        $course = $this->assignment?->unit?->course;

        $data = [
            'type' => 'assignment',
            'submission_id' => $this->id,
            'student_name' => $this->user?->name,
            'student_email' => $this->user?->email,
            'assignment_id' => $this->assignment_id,
            'assignment_title' => $this->assignment?->title,
            'submission_type' => $submissionType,
            'submission_type_label' => $this->enumLabel($this->assignment?->submission_type),
            'course' => $course ? [
                'id' => $course->id,
                'slug' => $course->slug,
                'title' => $course->title,
                'code' => $course->code,
            ] : null,
            'course_slug' => $course?->slug,
            'sequence' => $this->sequence($this->assignment?->unit?->order, $this->assignment?->order),
            'submitted_at' => $this->submitted_at,
            'status' => $statusValue,
            'status_value' => $statusValue,
            'status_label' => $this->enumLabel($this->status),
            'workflow_state' => $workflowValue,
            'workflow_state_value' => $workflowValue,
            'workflow_state_label' => $this->enumLabel($this->state),
            'score' => $this->score,
            'grade' => $this->whenLoaded('grade', fn () => $this->grade ? [
                'id' => $this->grade->id,
                'score' => $this->grade->score,
                'max_score' => $this->grade->max_score,
                'feedback' => $this->grade->feedback,
                'is_draft' => (bool) $this->grade->is_draft,
                'is_ai_assisted' => (bool) $this->grade->is_ai_assisted,
                'ai_suggested_score' => $this->grade->ai_suggested_score,
                'ai_reasoning' => $this->grade->ai_reasoning,
                'ai_scoring_reference' => $this->grade->ai_scoring_reference,
                'graded_at' => $this->grade->graded_at,
            ] : null),
        ];

        if ($this->isDetailView) {
            $data['question_text'] = $this->assignment?->description;
            $data['text'] = in_array($submissionType, ['text', 'mixed'], true)
                ? $this->answer_text
                : null;
            $data['files'] = in_array($submissionType, ['file', 'mixed'], true)
                ? $this->getSubmissionFiles()
                : [];
        }

        return $data;
    }

    private function toQuizArray(): array
    {
        $statusValue = $this->status instanceof \BackedEnum ? $this->status->value : $this->status;
        $workflowValue = $this->grading_status instanceof \BackedEnum ? $this->grading_status->value : $this->grading_status;
        $course = $this->quiz?->unit?->course;

        return [
            'type' => 'quiz',
            'submission_id' => $this->id,
            'student_name' => $this->user?->name,
            'student_email' => $this->user?->email,
            'quiz_id' => $this->quiz_id,
            'quiz_title' => $this->quiz?->title,
            'course' => $course ? [
                'id' => $course->id,
                'slug' => $course->slug,
                'title' => $course->title,
                'code' => $course->code,
            ] : null,
            'course_slug' => $course?->slug,
            'sequence' => $this->sequence($this->quiz?->unit?->order, $this->quiz?->order),
            'submitted_at' => $this->submitted_at,
            'status' => $statusValue,
            'status_value' => $statusValue,
            'status_label' => $this->enumLabel($this->status),
            'grading_status' => $workflowValue,
            'grading_status_label' => $this->enumLabel($this->grading_status),
            'workflow_state' => $workflowValue,
            'workflow_state_value' => $workflowValue,
            'workflow_state_label' => $this->enumLabel($this->grading_status),
            'score' => $this->score,
            'final_score' => $this->final_score,
        ];
    }

    private function toQuizEssayRowArray($submission, $essayAnswer): array
    {
        $statusValue = $submission->status instanceof \BackedEnum ? $submission->status->value : $submission->status;
        $workflowValue = $submission->grading_status instanceof \BackedEnum ? $submission->grading_status->value : $submission->grading_status;
        $course = $submission->quiz?->unit?->course;
        $question = $essayAnswer->question;

        $data = [
            'type' => 'quiz',
            'row_type' => 'essay_question',
            'submission_id' => $submission->id,
            'quiz_answer_id' => $essayAnswer->id,
            'student_name' => $submission->user?->name,
            'student_email' => $submission->user?->email,
            'quiz_id' => $submission->quiz_id,
            'quiz_title' => $submission->quiz?->title,
            'course' => $course ? [
                'id' => $course->id,
                'slug' => $course->slug,
                'title' => $course->title,
                'code' => $course->code,
            ] : null,
            'course_slug' => $course?->slug,
            'sequence' => $this->sequence($submission->quiz?->unit?->order, $submission->quiz?->order),
            'submitted_at' => $submission->submitted_at,
            'status' => $statusValue,
            'status_value' => $statusValue,
            'status_label' => $this->enumLabel($submission->status),
            'grading_status' => $workflowValue,
            'grading_status_label' => $this->enumLabel($submission->grading_status),
            'workflow_state' => $workflowValue,
            'workflow_state_value' => $workflowValue,
            'workflow_state_label' => $this->enumLabel($submission->grading_status),
            'score' => $submission->score,
            'final_score' => $submission->final_score,
            'question_id' => $essayAnswer->quiz_question_id,
            'question_text' => $question?->content,
            'question_type' => $question?->type?->value,
            'question_order' => $question?->order,
            'question_weight' => $question?->weight,
            'question_max_score' => $question?->max_score,
            'is_graded' => $essayAnswer->score !== null,
            'is_ai_assisted' => (bool) $essayAnswer->is_ai_assisted,
            'ai_suggested_score' => $essayAnswer->ai_suggested_score,
            'ai_reasoning' => $essayAnswer->ai_reasoning,
            'ai_scoring_reference' => $essayAnswer->ai_scoring_reference,
            'answered_at' => $essayAnswer->created_at,
            'answered_updated_at' => $essayAnswer->updated_at,
        ];

        if ($this->isDetailView) {
            $data['student_answer'] = $essayAnswer->content;
            $data['answer_feedback'] = $essayAnswer->feedback;
            $data['answer_selected_options'] = $essayAnswer->selected_options;
            $data['answer_is_auto_graded'] = $essayAnswer->is_auto_graded;
            $data['answer_score'] = $essayAnswer->score;
        }

        return $data;
    }

    private function getSubmissionFiles(): array
    {
        return $this->getMedia('submission_files')
            ->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'size' => $media->size,
                'mime_type' => $media->mime_type,
            ])
            ->values()
            ->toArray();
    }

    private function enumLabel(mixed $enum): ?string
    {
        if (! is_object($enum) || ! method_exists($enum, 'label')) {
            return null;
        }

        return $enum->label();
    }

    private function sequence(?int $unitOrder, ?int $elementOrder): ?string
    {
        if ($unitOrder === null || $elementOrder === null) {
            return null;
        }

        return $unitOrder.'.'.$elementOrder;
    }
}
