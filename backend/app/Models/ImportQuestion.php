<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A DRAFT question waiting for review. Its fields use the same names as the question editor form
 * (body, option_1..4, correct, explanation, difficulty) so QuestionService can save it as-is.
 */
class ImportQuestion extends Model
{
    protected $fillable = [
        'question_import_id', 'position', 'number', 'part', 'kind', 'body',
        'option_1', 'option_2', 'option_3', 'option_4', 'correct', 'explanation', 'difficulty',
        'page', 'warnings', 'status', 'question_id',
    ];

    protected function casts(): array
    {
        return ['warnings' => 'array', 'correct' => 'integer'];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(QuestionImport::class, 'question_import_id');
    }

    /** error = cannot be added until fixed; warning = added only after the admin has looked at it. */
    public function severity(): string
    {
        $levels = collect($this->warnings ?? [])->pluck('level');

        return $levels->contains('error') ? 'error' : ($levels->isNotEmpty() ? 'warning' : 'ok');
    }

    /** Values for the question editor / QuestionService. */
    public function toForm(): array
    {
        return $this->only(['body', 'option_1', 'option_2', 'option_3', 'option_4', 'correct', 'explanation', 'difficulty']);
    }

    /** Short label such as "Q72 · Part 3". */
    public function label(): string
    {
        return collect([$this->number ? 'Q'.$this->number : null, $this->part])->filter()->implode(' · ') ?: 'Question '.($this->position + 1);
    }
}
