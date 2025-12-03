<?php

namespace Modules\Auth\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserActivity;
use Tests\TestCase;

class UserActivityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_create_user_activity(): void
    {
        $activity = UserActivity::create([
            'user_id' => $this->user->id,
            'activity_type' => UserActivity::TYPE_ENROLLMENT,
            'activity_data' => ['course_id' => 1, 'course_name' => 'Test Course'],
        ]);

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $this->user->id,
            'activity_type' => UserActivity::TYPE_ENROLLMENT,
        ]);
    }

    public function test_activity_data_is_cast_to_array(): void
    {
        $activity = UserActivity::create([
            'user_id' => $this->user->id,
            'activity_type' => UserActivity::TYPE_COMPLETION,
            'activity_data' => ['course_id' => 1],
        ]);

        $this->assertIsArray($activity->activity_data);
        $this->assertEquals(['course_id' => 1], $activity->activity_data);
    }

    public function test_scope_of_type_filters_by_activity_type(): void
    {
        UserActivity::create([
            'user_id' => $this->user->id,
            'activity_type' => UserActivity::TYPE_ENROLLMENT,
            'activity_data' => [],
        ]);

        UserActivity::create([
            'user_id' => $this->user->id,
            'activity_type' => UserActivity::TYPE_COMPLETION,
            'activity_data' => [],
        ]);

        $enrollments = UserActivity::ofType(UserActivity::TYPE_ENROLLMENT)->get();

        $this->assertCount(1, $enrollments);
        $this->assertEquals(UserActivity::TYPE_ENROLLMENT, $enrollments->first()->activity_type);
    }

    public function test_scope_in_date_range_filters_by_date(): void
    {
        $oldActivity = UserActivity::create([
            'user_id' => $this->user->id,
            'activity_type' => UserActivity::TYPE_ENROLLMENT,
            'activity_data' => [],
        ]);
        $oldActivity->created_at = now()->subDays(10);
        $oldActivity->save();

        $newActivity = UserActivity::create([
            'user_id' => $this->user->id,
            'activity_type' => UserActivity::TYPE_COMPLETION,
            'activity_data' => [],
        ]);

        $recentActivities = UserActivity::inDateRange(now()->subDays(5), now())->get();

        $this->assertCount(1, $recentActivities);
        $this->assertEquals($newActivity->id, $recentActivities->first()->id);
    }
}
