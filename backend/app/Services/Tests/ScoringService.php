<?php

namespace App\Services\Tests;

use App\Models\Test;
use Illuminate\Support\Collection;

/**
 * Marks an attempt. Same rules as the student app (student-app/src/lib/test-engine.ts):
 *  - correct: +marks_correct, wrong: -marks_wrong, unanswered: 0
 *  - "answered & marked for review" counts for evaluation.
 */
class ScoringService
{
    /**
     * @param  Collection<int, int|null>  $selectedByQuestion  question_id => selected option id (null = skipped)
     * @return array{correct:int, incorrect:int, skipped:int, score:float}
     */
    public function score(Test $test, Collection $selectedByQuestion): array
    {
        $questions = $test->questions()->with('correctOption:id,question_id')->get(['questions.id']);

        $correct = $incorrect = 0;
        foreach ($questions as $question) {
            $chosen = $selectedByQuestion->get($question->id);
            if ($chosen === null) {
                continue;
            }
            $chosen === $question->correctOption?->id ? $correct++ : $incorrect++;
        }

        return [
            'correct' => $correct,
            'incorrect' => $incorrect,
            'skipped' => $questions->count() - $correct - $incorrect,
            'score' => $correct * $test->marks_correct - $incorrect * $test->marks_wrong,
        ];
    }
}
