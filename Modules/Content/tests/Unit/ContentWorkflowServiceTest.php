<?php

namespace Modules\Content\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Auth\Models\User;
use Modules\Content\Enums\ContentStatus;
use Modules\Content\Events\ContentApproved;
use Modules\Content\Events\ContentPublished;
use Modules\Content\Events\ContentRejected;
use Modules\Content\Events\ContentScheduled;
use Modules\Content\Events\ContentSubmitted;
use Modules\Content\Exceptions\InvalidTransitionException;
use Modules\Content\Models\ContentWorkflowHistory;
use Modules\Content\Models\News;
use Modules\Content\Services\ContentWorkflowService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ContentWorkflowService $workflowService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflowService = new ContentWorkflowService;
        $this->user = User::factory()->create();
    }

    #[Test]
    public function valid_transition_from_draft_to_submitted_succeeds()
    {
        $news = News::factory()->create(['status' => 'draft']);

        $result = $this->workflowService->transition(
            $news,
            ContentWorkflowService::STATE_SUBMITTED,
            $this->user
        );

        $this->assertTrue($result);
        $this->assertEquals(ContentStatus::Submitted, $news->fresh()->status);
    }

    #[Test]
    public function invalid_transition_throws_exception()
    {
        $news = News::factory()->create(['status' => 'draft']);

        $this->expectException(InvalidTransitionException::class);
        $this->expectExceptionMessage('Cannot transition from draft to published');

        $this->workflowService->transition(
            $news,
            ContentWorkflowService::STATE_PUBLISHED,
            $this->user
        );
    }

    #[Test]
    public function workflow_history_is_recorded_on_transition()
    {
        $news = News::factory()->create(['status' => 'draft']);

        $this->workflowService->transition(
            $news,
            ContentWorkflowService::STATE_SUBMITTED,
            $this->user,
            'Ready for review'
        );

        $this->assertDatabaseHas('content_workflow_history', [
            'content_type' => News::class,
            'content_id' => $news->id,
            'from_state' => 'draft',
            'to_state' => 'submitted',
            'user_id' => $this->user->id,
            'note' => 'Ready for review',
        ]);
    }

    #[Test]
    public function can_transition_checks_valid_transitions()
    {
        $this->assertTrue(
            $this->workflowService->canTransition('draft', 'submitted')
        );

        $this->assertFalse(
            $this->workflowService->canTransition('draft', 'published')
        );
    }

    #[Test]
    public function get_allowed_transitions_returns_correct_states()
    {
        $allowedTransitions = $this->workflowService->getAllowedTransitions('draft');

        $this->assertEquals(['submitted'], $allowedTransitions);
    }

    #[Test]
    public function submit_for_review_transitions_to_submitted()
    {
        Event::fake();
        $news = News::factory()->create(['status' => 'draft']);

        $result = $this->workflowService->submitForReview($news, $this->user);

        $this->assertTrue($result);
        $this->assertEquals(ContentStatus::Submitted, $news->fresh()->status);
        Event::assertDispatched(ContentSubmitted::class);
    }

    #[Test]
    public function approve_transitions_to_approved()
    {
        Event::fake();
        $news = News::factory()->create(['status' => 'in_review']);

        $result = $this->workflowService->approve($news, $this->user, 'Looks good');

        $this->assertTrue($result);
        $this->assertEquals(ContentStatus::Approved, $news->fresh()->status);
        Event::assertDispatched(ContentApproved::class);
    }

    #[Test]
    public function reject_transitions_to_rejected_with_reason()
    {
        Event::fake();
        $news = News::factory()->create(['status' => 'in_review']);

        $result = $this->workflowService->reject($news, $this->user, 'Needs more work');

        $this->assertTrue($result);
        $this->assertEquals(ContentStatus::Rejected, $news->fresh()->status);

        $history = ContentWorkflowHistory::where('content_id', $news->id)->latest()->first();
        $this->assertEquals('Needs more work', $history->note);

        Event::assertDispatched(ContentRejected::class);
    }

    #[Test]
    public function schedule_sets_scheduled_at_and_transitions()
    {
        Event::fake();
        $news = News::factory()->create(['status' => 'approved']);
        $publishDate = now()->addDays(7);

        $result = $this->workflowService->schedule($news, $this->user, $publishDate);

        $this->assertTrue($result);
        $this->assertEquals(ContentStatus::Scheduled, $news->fresh()->status);
        $this->assertNotNull($news->fresh()->scheduled_at);
        Event::assertDispatched(ContentScheduled::class);
    }

    #[Test]
    public function publish_sets_published_at_and_transitions()
    {
        Event::fake();
        $news = News::factory()->create(['status' => 'approved']);

        $result = $this->workflowService->publish($news, $this->user);

        $this->assertTrue($result);
        $this->assertEquals(ContentStatus::Published, $news->fresh()->status);
        $this->assertNotNull($news->fresh()->published_at);
        Event::assertDispatched(ContentPublished::class);
    }

    #[Test]
    public function complete_workflow_from_draft_to_published()
    {
        $news = News::factory()->create(['status' => 'draft']);

        // Draft -> Submitted
        $this->workflowService->submitForReview($news, $this->user);
        $this->assertEquals(ContentStatus::Submitted, $news->fresh()->status);

        // Submitted -> In Review
        $this->workflowService->transition($news, ContentWorkflowService::STATE_IN_REVIEW, $this->user);
        $this->assertEquals(ContentStatus::InReview, $news->fresh()->status);

        // In Review -> Approved
        $this->workflowService->approve($news, $this->user);
        $this->assertEquals(ContentStatus::Approved, $news->fresh()->status);

        // Approved -> Published
        $this->workflowService->publish($news, $this->user);
        $this->assertEquals(ContentStatus::Published, $news->fresh()->status);

        // Verify all history entries
        $historyCount = ContentWorkflowHistory::where('content_id', $news->id)->count();
        $this->assertEquals(4, $historyCount);
    }

    #[Test]
    public function rejected_content_can_return_to_draft()
    {
        $news = News::factory()->create(['status' => 'rejected']);

        $result = $this->workflowService->transition(
            $news,
            ContentWorkflowService::STATE_DRAFT,
            $this->user
        );

        $this->assertTrue($result);
        $this->assertEquals(ContentStatus::Draft, $news->fresh()->status);
    }

    #[Test]
    public function published_content_can_be_archived()
    {
        $news = News::factory()->create(['status' => 'published']);

        $result = $this->workflowService->transition(
            $news,
            ContentWorkflowService::STATE_ARCHIVED,
            $this->user
        );

        $this->assertTrue($result);
        $this->assertEquals(ContentStatus::Archived, $news->fresh()->status);
    }
}
