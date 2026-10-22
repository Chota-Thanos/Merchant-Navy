<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptAnswer extends Model
{
    protected $fillable = ['test_attempt_id', 'question_id', 'selected_option_id', 'marked_for_review', 'visited'];

    protected function casts(): array
    {
        return ['marked_for_review' => 'boolean', 'visited' => 'boolean'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'test_attempt_id');
    }
}
