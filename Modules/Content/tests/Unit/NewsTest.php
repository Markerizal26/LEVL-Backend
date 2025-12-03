<?php

namespace Modules\Content\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Content\Enums\ContentStatus;
use Modules\Content\Models\ContentCategory;
use Modules\Content\Models\ContentRead;
use Modules\Content\Models\News;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function news_belongs_to_author()
    {
        $user = User::factory()->create();
        $news = News::factory()->create(['author_id' => $user->id]);

        $this->assertInstanceOf(User::class, $news->author);
        $this->assertEquals($user->id, $news->author->id);
    }

    #[Test]
    public function news_has_many_categories()
    {
        $news = News::factory()->create();
        $categories = ContentCategory::factory()->count(3)->create();

        $news->categories()->attach($categories->pluck('id'));

        $this->assertCount(3, $news->categories);
    }

    #[Test]
    public function news_has_many_reads()
    {
        $news = News::factory()->published()->create();
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            ContentRead::create([
                'user_id' => $user->id,
                'readable_type' => News::class,
                'readable_id' => $news->id,
            ]);
        }

        $this->assertCount(3, $news->reads);
    }

    #[Test]
    public function scope_published_returns_only_published_news()
    {
        News::factory()->published()->count(3)->create();
        News::factory()->count(2)->create(['status' => 'draft']);

        $published = News::published()->get();

        $this->assertCount(3, $published);
        $this->assertTrue($published->every(fn ($n) => $n->status === ContentStatus::Published));
    }

    #[Test]
    public function scope_featured_returns_only_featured_news()
    {
        News::factory()->featured()->count(2)->create();
        News::factory()->count(3)->create(['is_featured' => false]);

        $featured = News::featured()->get();

        $this->assertCount(2, $featured);
        $this->assertTrue($featured->every(fn ($n) => $n->is_featured));
    }

    #[Test]
    public function is_published_returns_true_for_published_news()
    {
        $news = News::factory()->published()->create();

        $this->assertTrue($news->isPublished());
    }

    #[Test]
    public function is_published_returns_false_for_draft_news()
    {
        $news = News::factory()->create(['status' => 'draft']);

        $this->assertFalse($news->isPublished());
    }

    #[Test]
    public function is_scheduled_returns_true_for_scheduled_news()
    {
        $news = News::factory()->scheduled()->create();

        $this->assertTrue($news->isScheduled());
    }

    #[Test]
    public function is_scheduled_returns_false_for_published_news()
    {
        $news = News::factory()->published()->create();

        $this->assertFalse($news->isScheduled());
    }

    #[Test]
    public function increment_views_increases_view_count()
    {
        $news = News::factory()->create(['views_count' => 5]);

        $news->incrementViews();

        $this->assertEquals(6, $news->fresh()->views_count);
    }

    #[Test]
    public function get_trending_score_calculates_correctly()
    {
        $news = News::factory()->published()->create([
            'views_count' => 100,
            'published_at' => now()->subHours(10),
        ]);

        $score = $news->getTrendingScore();

        $this->assertEquals(10.0, $score); // 100 views / 10 hours
    }

    #[Test]
    public function get_trending_score_returns_zero_for_unpublished()
    {
        $news = News::factory()->create([
            'status' => 'draft',
            'published_at' => null,
            'views_count' => 100,
        ]);

        $score = $news->getTrendingScore();

        $this->assertEquals(0.0, $score);
    }

    #[Test]
    public function slug_is_auto_generated_from_title()
    {
        $news = News::factory()->create(['title' => 'Test News Title', 'slug' => null]);

        $this->assertEquals('test-news-title', $news->slug);
    }

    #[Test]
    public function custom_slug_is_preserved()
    {
        $news = News::factory()->create([
            'title' => 'Test News Title',
            'slug' => 'custom-slug',
        ]);

        $this->assertEquals('custom-slug', $news->slug);
    }

    #[Test]
    public function soft_delete_works_correctly()
    {
        $news = News::factory()->create();
        $newsId = $news->id;

        $news->delete();

        $this->assertSoftDeleted('news', ['id' => $newsId]);
        $this->assertNotNull($news->fresh()->deleted_at);
    }

    #[Test]
    public function featured_news_can_be_created()
    {
        $news = News::factory()->featured()->create();

        $this->assertTrue($news->is_featured);
    }

    #[Test]
    public function news_with_image_can_be_created()
    {
        $news = News::factory()->withImage()->create();

        $this->assertNotNull($news->featured_image_path);
        $this->assertStringContainsString('news/', $news->featured_image_path);
    }
}
