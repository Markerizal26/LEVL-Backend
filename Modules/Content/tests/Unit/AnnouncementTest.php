<?php

namespace Modules\Content\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Content\Enums\ContentStatus;
use Modules\Content\Enums\Priority;
use Modules\Content\Enums\TargetType;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\ContentRead;
use Modules\Content\Models\ContentRevision;
use Modules\Schemes\Models\Course;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function announcement_belongs_to_author()
    {
        $user = User::factory()->create();
        $announcement = Announcement::factory()->create(['author_id' => $user->id]);

        $this->assertInstanceOf(User::class, $announcement->author);
        $this->assertEquals($user->id, $announcement->author->id);
    }

    /** @test */
    public function announcement_belongs_to_course()
    {
        $course = Course::factory()->create();
        $announcement = Announcement::factory()->forCourse()->create(['course_id' => $course->id]);

        $this->assertInstanceOf(Course::class, $announcement->course);
        $this->assertEquals($course->id, $announcement->course->id);
    }

    /** @test */
    public function announcement_can_have_null_course()
    {
        $announcement = Announcement::factory()->create(['course_id' => null]);

        $this->assertNull($announcement->course_id);
        $this->assertNull($announcement->course);
    }

    /** @test */
    public function announcement_has_many_reads()
    {
        $announcement = Announcement::factory()->published()->create();
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            ContentRead::create([
                'user_id' => $user->id,
                'readable_type' => Announcement::class,
                'readable_id' => $announcement->id,
            ]);
        }

        $this->assertCount(3, $announcement->reads);
    }

    /** @test */
    public function announcement_has_many_revisions()
    {
        $announcement = Announcement::factory()->create();
        $editor = User::factory()->create();

        ContentRevision::factory()->count(2)->create([
            'content_type' => Announcement::class,
            'content_id' => $announcement->id,
            'editor_id' => $editor->id,
        ]);

        $this->assertCount(2, $announcement->revisions);
    }

    /** @test */
    public function scope_published_returns_only_published_announcements()
    {
        Announcement::factory()->published()->count(3)->create();
        Announcement::factory()->count(2)->create(['status' => 'draft']);

        $published = Announcement::published()->get();

        $this->assertCount(3, $published);
        $this->assertTrue($published->every(fn ($a) => $a->status === ContentStatus::Published));
    }

    /** @test */
    public function scope_for_course_filters_by_course()
    {
        $course = Course::factory()->create();
        Announcement::factory()->forCourse()->count(3)->create(['course_id' => $course->id]);
        Announcement::factory()->count(2)->create(['course_id' => null]);

        $courseAnnouncements = Announcement::forCourse($course->id)->get();

        $this->assertCount(3, $courseAnnouncements);
        $this->assertTrue($courseAnnouncements->every(fn ($a) => $a->course_id === $course->id));
    }

    /** @test */
    public function is_published_returns_true_for_published_announcement()
    {
        $announcement = Announcement::factory()->published()->create();

        $this->assertTrue($announcement->isPublished());
    }

    /** @test */
    public function is_published_returns_false_for_draft_announcement()
    {
        $announcement = Announcement::factory()->create(['status' => 'draft']);

        $this->assertFalse($announcement->isPublished());
    }

    /** @test */
    public function is_published_returns_false_for_future_scheduled_announcement()
    {
        $announcement = Announcement::factory()->create([
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $this->assertFalse($announcement->isPublished());
    }

    /** @test */
    public function is_scheduled_returns_true_for_scheduled_announcement()
    {
        $announcement = Announcement::factory()->scheduled()->create();

        $this->assertTrue($announcement->isScheduled());
    }

    /** @test */
    public function is_scheduled_returns_false_for_published_announcement()
    {
        $announcement = Announcement::factory()->published()->create();

        $this->assertFalse($announcement->isScheduled());
    }

    /** @test */
    public function mark_as_read_by_creates_content_read_record()
    {
        $announcement = Announcement::factory()->published()->create();
        $user = User::factory()->create();

        $announcement->markAsReadBy($user);

        $this->assertDatabaseHas('content_reads', [
            'user_id' => $user->id,
            'readable_type' => Announcement::class,
            'readable_id' => $announcement->id,
        ]);
    }

    /** @test */
    public function mark_as_read_by_does_not_create_duplicate_records()
    {
        $announcement = Announcement::factory()->published()->create();
        $user = User::factory()->create();

        $announcement->markAsReadBy($user);
        $announcement->markAsReadBy($user);

        $this->assertEquals(1, ContentRead::where('user_id', $user->id)
            ->where('readable_id', $announcement->id)
            ->count());
    }

    /** @test */
    public function is_read_by_returns_true_when_user_has_read()
    {
        $announcement = Announcement::factory()->published()->create();
        $user = User::factory()->create();

        $announcement->markAsReadBy($user);

        $this->assertTrue($announcement->isReadBy($user));
    }

    /** @test */
    public function is_read_by_returns_false_when_user_has_not_read()
    {
        $announcement = Announcement::factory()->published()->create();
        $user = User::factory()->create();

        $this->assertFalse($announcement->isReadBy($user));
    }

    /** @test */
    public function soft_delete_works_correctly()
    {
        $announcement = Announcement::factory()->create();
        $announcementId = $announcement->id;

        $announcement->delete();

        $this->assertSoftDeleted('announcements', ['id' => $announcementId]);
        $this->assertNotNull($announcement->fresh()->deleted_at);
    }

    /** @test */
    public function high_priority_announcement_can_be_created()
    {
        $announcement = Announcement::factory()->highPriority()->create();

        $this->assertEquals(Priority::High, $announcement->priority);
    }

    /** @test */
    public function announcement_target_type_defaults_to_all()
    {
        $announcement = Announcement::factory()->create();

        $this->assertEquals(TargetType::All, $announcement->target_type);
    }

    /** @test */
    public function announcement_can_target_specific_role()
    {
        $announcement = Announcement::factory()->forRole('instructor')->create();

        $this->assertEquals(TargetType::Role, $announcement->target_type);
        $this->assertEquals('instructor', $announcement->target_value);
    }
}
