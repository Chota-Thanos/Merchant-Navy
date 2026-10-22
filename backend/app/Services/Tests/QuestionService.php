<?php

namespace App\Services\Tests;

use App\Models\Question;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Saves a question together with its 4 options.
 * The admin form uses flat fields: option_1..option_4 and `correct` (1–4); see Filament/Support/QuestionForm.php.
 */
class QuestionService
{
    public function save(array $data, ?Question $question = null): Question
    {
        return DB::transaction(function () use ($data, $question) {
            $question ??= new Question;
            $question->fill(Arr::only($data, ['subject_id', 'chapter_id', 'body', 'explanation', 'difficulty']))->save();

            foreach (range(1, 4) as $position) {
                $question->options()->updateOrCreate(
                    ['position' => $position],
                    ['body' => $data["option_{$position}"], 'is_correct' => (int) $data['correct'] === $position],
                );
            }

            return $question;
        });
    }

    /** Values for the edit form. */
    public function toForm(Question $question): array
    {
        $data = $question->only(['subject_id', 'chapter_id', 'body', 'explanation', 'difficulty']);

        foreach ($question->options as $option) {
            $data["option_{$option->position}"] = $option->body;
            if ($option->is_correct) {
                $data['correct'] = $option->position;
            }
        }

        return $data;
    }

    public function duplicate(Question $question): Question
    {
        return $this->save($this->toForm($question), null);
    }

    /** "B" for the second option. */
    public function correctLetter(Question $question): string
    {
        $position = $question->options->firstWhere('is_correct', true)?->position;

        return $position ? chr(64 + $position) : '?';
    }
}
