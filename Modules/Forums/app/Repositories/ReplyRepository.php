<?php

namespace Modules\Forums\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Forums\Models\Reply;

class ReplyRepository extends BaseRepository
{
    protected function model(): string
    {
        return Reply::class;
    }

    protected array $allowedFilters = ['thread_id', 'parent_id', 'is_accepted_answer'];

    protected array $allowedSorts = ['id', 'created_at', 'is_accepted_answer'];

    protected string $defaultSort = 'created_at';

    protected array $with = ['author'];

    public function getRepliesForThread(int $threadId): Collection
    {
        return Reply::where('thread_id', $threadId)
            ->with(['author', 'children.author', 'children.children.author'])
            ->withCount('reactions')
            ->orderBy('is_accepted_answer', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getTopLevelReplies(int $threadId): Collection
    {
        return Reply::where('thread_id', $threadId)
            ->topLevel()
            ->with(['author', 'children.author'])
            ->withCount('reactions')
            ->orderBy('is_accepted_answer', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getNestedReplies(int $parentId): Collection
    {
        return Reply::where('parent_id', $parentId)
            ->with(['author', 'children.author'])
            ->withCount('reactions')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function create(array $data): Reply
    {
        return Reply::create($data);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model->fresh();
    }

    public function updateReply(Reply $reply, array $data): Reply
    {
        $reply->update($data);

        return $reply->fresh();
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function deleteReply(Reply $reply, ?int $deletedBy = null): bool
    {
        if ($deletedBy) {
            $reply->deleted_by = $deletedBy;
            $reply->save();
        }

        return $reply->delete();
    }

    public function findWithRelations(int $replyId): ?Reply
    {
        return Reply::with(['author', 'thread', 'parent', 'children'])
            ->withCount('reactions')
            ->find($replyId);
    }

    public function getAcceptedAnswer(int $threadId): ?Reply
    {
        return Reply::where('thread_id', $threadId)
            ->accepted()
            ->with(['author'])
            ->first();
    }

    public function markAsAccepted(Reply $reply): bool
    {
        Reply::where('thread_id', $reply->thread_id)
            ->where('is_accepted_answer', true)
            ->update(['is_accepted_answer' => false]);

        $reply->is_accepted_answer = true;

        return $reply->save();
    }

    public function unmarkAsAccepted(Reply $reply): bool
    {
        $reply->is_accepted_answer = false;

        return $reply->save();
    }
}
