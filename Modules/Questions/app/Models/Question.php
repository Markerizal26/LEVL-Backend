<?php

namespace Modules\Questions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Models\User;
use Modules\Common\Models\Category;
use Modules\Questions\Enums\QuestionDifficulty;
use Modules\Questions\Enums\QuestionStatus;
use Modules\Questions\Enums\QuestionType;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Question extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'category_id',
        'created_by',
        'type',
        'difficulty',
        'question_text',
        'explanation',
        'points',
        'tags',
        'meta',
        'usage_count',
        'last_used_at',
        'status',
    ];

    protected $casts = [
        'type' => QuestionType::class,
        'difficulty' => QuestionDifficulty::class,
        'status' => QuestionStatus::class,
        'tags' => 'array',
        'meta' => 'array',
        'points' => 'integer',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'difficulty', 'question_text', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function usages(): MorphMany
    {
        return $this->morphMany(QuestionUsage::class, 'usable');
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', QuestionStatus::ACTIVE);
    }

    public function scopeByType($query, QuestionType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDifficulty($query, QuestionDifficulty $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where('question_text', 'ILIKE', "%{$search}%");
    }
}
