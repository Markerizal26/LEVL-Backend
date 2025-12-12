<?php

namespace Modules\Learning\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Learning\Contracts\Repositories\AssignmentRepositoryInterface;
use Modules\Learning\Models\Assignment;
use Modules\Schemes\Models\Lesson;

class AssignmentRepository implements AssignmentRepositoryInterface
{
  public function listForLesson(Lesson $lesson, array $filters = []): Collection
  {
    $query = Assignment::query()
      ->where("lesson_id", $lesson->id)
      ->with(["creator:id,name,email", "lesson:id,title,slug"]);

    $status = $filters["status"] ?? ($filters["filter"]["status"] ?? null);
    if ($status) {
      $query->where("status", $status);
    }

    // Add default limit to prevent unbounded queries
    $limit = $filters["limit"] ?? 100;

    return $query->orderBy("created_at", "desc")->limit($limit)->get();
  }

  public function create(array $attributes): Assignment
  {
    return Assignment::create($attributes);
  }

  public function update(Assignment $assignment, array $attributes): Assignment
  {
    $assignment->fill($attributes)->save();

    return $assignment;
  }

  public function delete(Assignment $assignment): bool
  {
    return $assignment->delete();
  }
}
