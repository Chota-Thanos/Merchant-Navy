<?php

namespace App\Services\Imports;

use App\Enums\ImportMethod;
use App\Jobs\ProcessQuestionImport;
use App\Models\Admin;
use App\Models\ImportQuestion;
use App\Models\Question;
use App\Models\QuestionImport;
use App\Models\Test;
use App\Services\Tests\QuestionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Question imports, step by step:
 *   start()   keeps the uploaded file and creates the import
 *   process() reads the file into DRAFT questions (import_questions) — nothing is added to the bank yet
 *   commit()  after the admin's review, saves the chosen drafts as real questions and puts them in a test
 */
class QuestionImportService
{
    /** Most questions one test can hold (same limit as the test builder). */
    public const MAX_PER_TEST = 200;

    public function __construct(
        private PdfRuleParser $pdf,
        private ExcelParser $excel,
        private AiPdfParser $ai,
        private QuestionService $questions,
    ) {}

    /**
     * Stores the file and starts reading it. The automatic PDF reader and Excel are fast and run right away;
     * the AI reader takes minutes and runs in the background (needs `php artisan queue:work`, or QUEUE_CONNECTION=sync).
     */
    public function start(string $storedPath, string $originalName, ImportMethod $method, ?Test $test, ?Admin $admin): QuestionImport
    {
        $import = QuestionImport::create([
            'admin_id' => $admin?->id,
            'test_id' => $test?->id,
            'method' => $method,
            'original_name' => $originalName,
            'file_path' => $storedPath,
            'status' => 'parsing',
        ]);

        $method === ImportMethod::PdfAi ? ProcessQuestionImport::dispatch($import->id) : $this->process($import);

        return $import->refresh();
    }

    /** Reads the file and replaces the import's drafts. Never throws: a failure is stored on the import. */
    public function process(QuestionImport $import): void
    {
        set_time_limit(0);
        $import->update(['status' => 'parsing', 'progress' => 0, 'error' => null]);

        try {
            $parser = match ($import->method) {
                ImportMethod::Pdf => $this->pdf,
                ImportMethod::PdfAi => $this->ai,
                ImportMethod::Excel => $this->excel,
            };
            $result = $parser->parse(
                Storage::disk('local')->path($import->file_path),
                fn (int $percent) => $import->update(['progress' => min(99, $percent)]),
            );

            DB::transaction(function () use ($import, $result) {
                $import->drafts()->delete();
                foreach ($result->questions as $position => $draft) {
                    $verified = $draft['_verified'];
                    unset($draft['_answer_text'], $draft['_verified']);
                    $import->drafts()->create($draft + ['position' => $position]);
                }
                $import->update([
                    'status' => 'ready',
                    'progress' => 100,
                    'summary' => [
                        'expected' => $result->expected,
                        'notes' => $result->notes,
                        'key_verified' => collect($result->questions)->where('_verified', true)->count(),
                        'key_entries' => count($result->answerKey),
                    ],
                ]);
            });
        } catch (Throwable $e) {
            report($e);
            $import->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Saves reviewed drafts as real questions and (optionally) puts them in a test, in file order.
     * Drafts with an error are skipped. A question that already exists with identical text and options is reused.
     *
     * @param  array<int,int>  $draftIds
     * @return array{added:int, reused:int, blocked:int, over_limit:int}
     */
    public function commit(QuestionImport $import, array $draftIds, ?Test $test): array
    {
        $counts = ['added' => 0, 'reused' => 0, 'blocked' => 0, 'over_limit' => 0];

        DB::transaction(function () use ($import, $draftIds, $test, &$counts) {
            $drafts = $import->drafts()->whereIn('id', $draftIds)->where('status', '!=', 'added')->get();
            $room = $test ? self::MAX_PER_TEST - $test->questions()->count() : PHP_INT_MAX;
            $position = $test ? (\DB::table('test_questions')->where('test_id', $test->id)->max('position') ?? -1) + 1 : 0;

            foreach ($drafts as $draft) {
                if ($draft->severity() === 'error') {
                    $counts['blocked']++;

                    continue;
                }
                if ($test && $room <= 0) {
                    $counts['over_limit']++;

                    continue;
                }

                $existing = $this->identicalQuestion($draft);
                $question = $existing ?? $this->questions->save($draft->toForm());
                $existing ? $counts['reused']++ : $counts['added']++;

                if ($test && ! $test->questions()->whereKey($question->id)->exists()) {
                    $test->questions()->attach($question->id, ['position' => $position++]);
                    $room--;
                }
                $draft->update(['status' => 'added', 'question_id' => $question->id]);
            }
        });

        return $counts;
    }

    /** An existing bank question with exactly this text and these options (so re-importing never duplicates). */
    private function identicalQuestion(ImportQuestion $draft): ?Question
    {
        $norm = app(ImportValidator::class);
        $wanted = array_map(fn ($i) => $norm->norm((string) $draft["option_{$i}"]), range(1, 4));

        return Question::where('body', $draft->body)->with('options')->get()->first(
            fn (Question $q) => $q->options->pluck('body')->map(fn ($b) => $norm->norm((string) $b))->all() === $wanted
                && $q->options->firstWhere('is_correct', true)?->position === $draft->correct,
        );
    }
}
