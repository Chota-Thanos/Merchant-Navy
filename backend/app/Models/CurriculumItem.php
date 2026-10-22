<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** One entry on a course page: either a Subject or a (mock) Test. */
class CurriculumItem extends Model
{
    protected $fillable = ['course_id', 'item_type', 'item_id', 'position'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function item(): MorphTo
    {
        return $this->morphTo();
    }
}
