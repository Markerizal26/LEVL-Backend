<?php

namespace Modules\Auth\Traits;

use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\UserActivity;

trait TracksUserActivity
{
  public function logActivity(string $type, array $data = [], ?Model $related = null): UserActivity
  {
    return UserActivity::create([
      "user_id" => $this->id,
      "activity_type" => $type,
      "activity_data" => $data,
      "related_type" => $related ? get_class($related) : null,
      "related_id" => $related?->id,
    ]);
  }

  public function getRecentActivities(int $limit = 10)
  {
    return $this->activities()->orderBy("created_at", "desc")->limit($limit)->get();
  }

  public function getLastActivityAttribute(): ?UserActivity
  {
    return $this->activities()->latest("created_at")->first();
  }

  public function getLastActiveRelativeAttribute(): ?string
  {
    $lastActivity = $this->lastActivity;

    if (!$lastActivity) {
      return null;
    }

    return $lastActivity->created_at->locale("id")->diffForHumans();
  }
}
