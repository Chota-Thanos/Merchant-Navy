<?php

namespace App\Models;

use App\Enums\TestKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A practice test (inside a chapter) or a mock test (in the course curriculum). */
class Test extends Model
{
    protected $fillable = ['course_id', 'title', 'kind', 'duration_minutes', 'marks_correct', 'marks_wrong', 'instructions', 'is_published'];

    protected function casts(): array
    {
        return [
            'kind' => TestKind::class,
            'marks_correct' => 'float',
            'marks_wrong' => 'float',
            'is_published' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'test_questions')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }
}
