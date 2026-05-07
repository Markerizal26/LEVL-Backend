<?php

declare(strict_types=1);

namespace Modules\Gamification\Listeners;

use Modules\Forums\Events\ReplyAccepted;
use Modules\Gamification\Services\EventCounterService;
use Modules\Gamification\Services\Support\BadgeRuleEvaluator;

class AwardGamificationForReplyAccepted extends GamificationListener
{
    public function __construct(
        private readonly EventCounterService $counterService,
        private readonly BadgeRuleEvaluator $evaluator
    ) {}

    public function handle(ReplyAccepted $event): void
    {
        $reply = $event->reply;
        $authorId = (int) ($reply->author_id ?? 0);

        if ($authorId <= 0) {
            return;
        }

        $this->counterService->incrementGlobal($authorId, 'reply_accepted');

        $user = $this->getCachedUser($authorId);
        if ($user) {
            $this->evaluator->evaluate($user, 'reply_accepted', [
                'reply_id' => $reply->id,
                'thread_id' => $reply->thread_id,
            ]);
        }
    }
}
