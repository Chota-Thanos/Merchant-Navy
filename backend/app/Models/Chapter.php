<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One entry in a subject's ordered list: a real chapter (with contents), or a mock test placed between chapters
 * (`test_id` is set and there are no contents).
 */
class Chapter extends Model
{
    protected $fillable = ['subject_id', 'test_id', 'title', 'position'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(ChapterContent::class)->orderBy('position');
    }

    public function isMockTest(): bool
    {
        return $this->test_id !== null;
    }

    /** Only real chapters (not the mock tests placed between them). */
    public function scopeRealChapters(Builder $query): void
    {
        $query->whereNull('test_id');
    }
}
