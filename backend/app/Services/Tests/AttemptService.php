<?php

namespace App\Services\Tests;

use App\Enums\AttemptStatus;
use App\Models\AttemptAnswer;
use App\Models\QuestionOption;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use App\Notifications\ResultReady;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The life of a test attempt: start/resume → save answers → submit (by the student or by the clock).
 * The server decides the time: `deadline_at` is set when the attempt starts and never trusts the browser.
 */
class AttemptService
{
    /** Seconds after the deadline during which a late save/submit from a slow connection is still accepted. */
    private const GRACE_SECONDS = 15;

    public function __construct(private ScoringService $scoring) {}

    /** Starts a new attempt, or resumes the unfinished one (e.g. after the browser was closed). */
    public function start(User $user, Test $test): TestAttempt
    {
        if ($test->questions()->doesntExist()) {
            throw ValidationException::withMessages(['test' => 'This test has no questions yet.']);
        }

        return DB::transaction(function () use ($user, $test) {
            $open = TestAttempt::where('user_id', $user->id)->where('test_id', $test->id)
                ->where('status', AttemptStatus::InProgress)->lockForUpdate()->latest('id')->first();

            if ($open && $open->deadline_at->isFuture()) {
                return $open;
            }
            if ($open) {
                $this->finish($open, AttemptStatus::AutoSubmitted); // time ran out while the student was away
            }

            return TestAttempt::create([
                'user_id' => $user->id,
                'test_id' => $test->id,
                'attempt_no' => TestAttempt::where('user_id', $user->id)->where('test_id', $test->id)->count() + 1,
                'status' => AttemptStatus::InProgress,
                'started_at' => $now = now(),
                'deadline_at' => $now->copy()->addMinutes($test->duration_minutes),
            ]);
        });
    }

    /** @param array{selectedOptionId:?int, markedForReview:bool, visited:bool} $data */
    public function saveAnswer(TestAttempt $attempt, int $questionId, array $data): void
    {
        abort_if($attempt->isFinished() || now()->gt($attempt->deadline_at->copy()->addSeconds(self::GRACE_SECONDS)), 409, 'This attempt has ended.');

        $this->store($attempt, $questionId, $data);
    }

    /**
     * Ends the attempt: scores it and records the time taken. Safe to call twice.
     *
     * @param  array<int|string, array>|null  $finalAnswers  the browser's last known answers (merged in before scoring)
     */
    public function submit(TestAttempt $attempt, ?array $finalAnswers = null): TestAttempt
    {
        if ($attempt->isFinished()) {
            return $attempt;
        }

        $late = now()->gt($attempt->deadline_at);

        // Answers sent with the final submit count only if they arrive within the allowed window.
        if ($finalAnswers && ! now()->gt($attempt->deadline_at->copy()->addSeconds(self::GRACE_SECONDS))) {
            foreach ($finalAnswers as $questionId => $data) {
                $this->store($attempt, (int) $questionId, $data);
            }
        }

        return $this->finish($attempt, $late ? AttemptStatus::AutoSubmitted : AttemptStatus::Submitted);
    }

    /** Submits every unfinished attempt whose time is up (run every minute by the scheduler). */
    public function autoSubmitExpired(): int
    {
        $expired = TestAttempt::where('status', AttemptStatus::InProgress)->where('deadline_at', '<', now())->get();
        $expired->each(fn (TestAttempt $a) => $this->finish($a, AttemptStatus::AutoSubmitted));

        return $expired->count();
    }

    // ------------------------------------------------------------------

    /** Saves one answer after checking the question belongs to the test and the option to the question. */
    private function store(TestAttempt $attempt, int $questionId, array $data): void
    {
        abort_unless($attempt->test->questions()->whereKey($questionId)->exists(), 422, 'Unknown question.');

        $optionId = $data['selectedOptionId'] ?? null;
        if ($optionId !== null) {
            abort_unless(QuestionOption::where('question_id', $questionId)->whereKey($optionId)->exists(), 422, 'Unknown option.');
        }

        AttemptAnswer::updateOrCreate(
            ['test_attempt_id' => $attempt->id, 'question_id' => $questionId],
            [
                'selected_option_id' => $optionId,
                'marked_for_review' => (bool) ($data['markedForReview'] ?? false),
                'visited' => (bool) ($data['visited'] ?? true),
            ],
        );
    }

    private function finish(TestAttempt $attempt, AttemptStatus $status): TestAttempt
    {
        return DB::transaction(function () use ($attempt, $status) {
            $attempt = TestAttempt::whereKey($attempt->id)->lockForUpdate()->first();
            if ($attempt->isFinished()) {
                return $attempt;
            }

            $test = $attempt->test;
            $selected = $attempt->answers()->pluck('selected_option_id', 'question_id');
            $result = $this->scoring->score($test, $selected);

            $now = now();
            $attempt->update($result + [
                'status' => $status,
                'submitted_at' => $now,
                // the clock stops at the deadline even if the student was away
                'time_taken_seconds' => min((int) round($attempt->started_at->diffInSeconds($now)), $test->duration_minutes * 60),
            ]);

            $attempt->user->notify(new ResultReady($attempt));

            return $attempt;
        });
    }
}
