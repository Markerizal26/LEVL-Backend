<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Enums\NotificationFrequency;
use Modules\Notifications\Enums\NotificationType;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'channel',
        'enabled',
        'frequency',
    ];

    protected $casts = [
        'category' => NotificationType::class,
        'channel' => NotificationChannel::class,
        'frequency' => NotificationFrequency::class,
        'enabled' => 'boolean',
    ];

    // Categories (kept for backward compatibility)
    const CATEGORY_COURSE_UPDATES = 'course_updates';

    const CATEGORY_ASSIGNMENTS = 'assignments';

    const CATEGORY_ASSESSMENTS = 'assessments';

    const CATEGORY_FORUM = 'forum';

    const CATEGORY_ACHIEVEMENTS = 'achievements';

    const CATEGORY_SYSTEM = 'system';

    // Channels (kept for backward compatibility)
    const CHANNEL_EMAIL = 'email';

    const CHANNEL_IN_APP = 'in_app';

    const CHANNEL_PUSH = 'push';

    // Frequency (kept for backward compatibility)
    const FREQUENCY_IMMEDIATE = 'immediate';

    const FREQUENCY_DAILY = 'daily';

    const FREQUENCY_WEEKLY = 'weekly';

    /**
     * Get all available categories.
     */
    public static function getCategories(): array
    {
        return NotificationType::values();
    }

    /**
     * Get all available channels.
     */
    public static function getChannels(): array
    {
        return NotificationChannel::values();
    }

    /**
     * Get all available frequencies.
     */
    public static function getFrequencies(): array
    {
        return NotificationFrequency::values();
    }

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
