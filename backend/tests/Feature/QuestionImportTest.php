<?php

namespace Tests\Feature;

use App\Enums\ImportMethod;
use App\Enums\TestKind;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionImport;
use App\Models\Test;
use App\Services\Imports\Ai\AiReaderManager;
use App\Services\Imports\AiPdfParser;
use App\Services\Imports\ExcelParser;
use App\Services\Imports\ImportValidator;
use App\Services\Imports\PdfRuleParser;
use App\Services\Imports\QuestionImportService;
use App\Services\Pdf\PdfTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

/**
 * Question import: PDF (automatic reader and AI reader), Excel, review and commit.
 * The PDF tests use a real 45-page, 100-question paper (tests/Fixtures/number-system-test.pdf) and need
 * Python with PyMuPDF; they are skipped where that is not installed.
 */
class QuestionImportTest extends TestCase
{
    use RefreshDatabase;

    private string $pdf;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdf = base_path('tests/Fixtures/number-system-test.pdf');
    }

    private function needsPdfTool(): void
    {
        if (! app(PdfTool::class)->available()) {
            $this->markTestSkipped('Python with PyMuPDF is not available.');
        }
    }

    private function test(string $title = 'Imported test'): Test
    {
        $course = Course::firstOrCreate(['slug' => 'c'], ['title' => 'Course']);

        return Test::create(['course_id' => $course->id, 'title' => $title, 'kind' => TestKind::Practice, 'duration_minutes' => 60]);
    }

    // ------------------------------------------------------------------ automatic PDF reader

    public function test_rule_parser_reads_all_100_questions_and_verifies_the_answer_key(): void
    {
        $this->needsPdfTool();
        Storage::fake('local');

        $result = app(PdfRuleParser::class)->parse($this->pdf);

        $this->assertCount(100, $result->questions);
        $this->assertSame(100, $result->expected);
        $this->assertSame(100, count($result->answerKey));
        $this->assertCount(100, array_filter($result->questions, fn ($q) => $q['_verified'] === true), 'every answer matches the answer key');
        $this->assertSame([], array_filter($result->questions, fn ($q) => $q['warnings']), 'no warnings expected on a clean paper');
        $this->assertSame(range(1, 100), array_column($result->questions, 'number'));

        $byNumber = array_column($result->questions, null, 'number');

        // A cube root symbol sits inside the sentence, not displaced
        $this->assertStringContainsString('The value of ∛729 is:', strip_tags($byNumber[72]['body']));
        $this->assertSame('9', $byNumber[72]['option_4']);
        $this->assertSame(4, $byNumber[72]['correct']);
        $this->assertSame('easy', $byNumber[72]['difficulty']);
        $this->assertSame('Part 3', $byNumber[72]['part']);

        // Options laid out in two columns and wrapped over several lines stay with the right option
        $this->assertSame('The difference of two natural numbers is always a natural number.', $byNumber[4]['option_1']);
        $this->assertSame('The sum of two natural numbers is always a natural number.', $byNumber[4]['option_2']);
        $this->assertSame('The quotient of two natural numbers is always a natural number.', $byNumber[4]['option_3']);
        $this->assertSame('The difference of two whole numbers is always a whole number.', $byNumber[4]['option_4']);
        $this->assertSame(2, $byNumber[4]['correct']); // question runs over a page break

        // Powers inside a sentence are read as powers ("2³ × 5² × 7", not "23 × 52 × 7")
        $this->assertStringContainsString('denominator 2³ × 5² × 7.', strip_tags($byNumber[38]['body']));

        // Explanation parts are kept, "revise" lines are not
        $this->assertStringContainsString('<strong>Working:</strong>', $byNumber[100]['explanation']);
        $this->assertStringNotContainsString('Revise in the chapter document', $byNumber[100]['explanation']);
        $this->assertStringContainsString('1965', strip_tags($byNumber[100]['explanation']));
    }

    public function test_rule_parser_reads_a_latex_paper_with_fractions_roots_vectors_and_powers(): void
    {
        $this->needsPdfTool();

        $result = app(PdfRuleParser::class)->parse(base_path('tests/Fixtures/vectors-latex.pdf'));
        $questions = $result->questions;
        $byNumber = array_column($questions, null, 'number');

        // Structure: 200 questions, "Question 6" lines at the top of a page included
        $this->assertSame(range(1, 200), array_column($questions, 'number'));
        $this->assertSame(['Topic 1' => 35, 'Topic 2' => 30, 'Topic 3' => 50, 'Topic 4' => 40, 'Topic 5' => 45], array_count_values(array_column($questions, 'part')));
        foreach ($questions as $q) {
            $this->assertSame(4, count(array_filter([$q['option_1'], $q['option_2'], $q['option_3'], $q['option_4']], 'filled')), "Q{$q['number']} has 4 options");
            $this->assertContains($q['correct'], [1, 2, 3, 4], "Q{$q['number']} has an answer");
        }
        $this->assertSame('hard', $byNumber[13]['difficulty']);
        $this->assertSame('calculation', $byNumber[100]['kind']);

        // The only warnings are the "rebuilt from the page layout" ones: no errors, no wrong answers, no false alarms
        $messages = collect($questions)->flatMap(fn ($q) => $q['warnings']);
        $this->assertSame(0, $messages->where('level', 'error')->count());
        $this->assertSame(0, $messages->filter(fn ($w) => ! str_contains($w['message'], 'rebuilt from the page layout'))->count());
        $this->assertGreaterThan(100, collect($questions)->filter(fn ($q) => ! $q['warnings'])->count(), 'most questions are clean');
        $this->assertStringContainsString('fraction and square root', $byNumber[100]['warnings'][0]['message']);

        // Stacked fraction, unit-vector hats, printed (bold) vectors
        $this->assertSame('16/3 <strong>î</strong> −4 <strong>ĵ</strong>', $byNumber[100]['option_1']);
        $this->assertSame('12<strong>î</strong> −9 <strong>ĵ</strong>', $byNumber[100]['option_4']);
        $this->assertStringContainsString('<strong>A</strong>', $byNumber[6]['body']);

        // Square roots with the bar over powers and indices
        $this->assertStringContainsString('A = √(8²+(−6)²) = 10', strip_tags($byNumber[100]['explanation']));
        $this->assertStringContainsString('R<sub>x</sub> = ΣF<sub>x</sub>', $byNumber[101]['explanation']);
        $this->assertStringContainsString('R = √(6²+8²) = √100 = 10 N', strip_tags($byNumber[101]['explanation']));
        $this->assertStringContainsString('F₁ = 4', strip_tags($byNumber[101]['body']));

        // Exponents that run over two characters stay on their line; options that differ only by a "/" stay different
        $this->assertSame('m²/s²', $byNumber[121]['option_2']);
        $this->assertSame('s⁻²', $byNumber[121]['option_3']);
        $this->assertNotSame($byNumber[66]['option_1'], $byNumber[66]['option_4']);

        // Degrees are degree signs, and "Why the others fail" is its own part of the solution
        $this->assertStringNotContainsString('^{', $byNumber[40]['option_1'].$byNumber[40]['explanation']);
        $this->assertStringContainsString('<strong>Why the others fail:</strong>', $byNumber[100]['explanation']);
    }

    public function test_import_review_then_commit_creates_questions_in_order_and_never_duplicates(): void
    {
        $this->needsPdfTool();
        Storage::fake('local');
        Storage::disk('local')->put('question-imports/paper.pdf', file_get_contents($this->pdf));
        $service = app(QuestionImportService::class);

        $import = $service->start('question-imports/paper.pdf', 'paper.pdf', ImportMethod::Pdf, null, null);

        $this->assertSame('ready', $import->status);
        $this->assertSame(100, $import->drafts()->count());
        $this->assertSame(0, Question::count(), 'nothing is added before the admin approves');
        $this->assertSame(100, $import->summary['key_verified']);

        $test = $this->test();
        $firstThirty = $import->drafts()->where('number', '<=', 30)->pluck('id')->all();
        $counts = $service->commit($import, $firstThirty, $test);

        $this->assertSame(30, $counts['added']);
        $this->assertSame(30, Question::count());
        $this->assertSame(range(1, 30), $test->questions()->get()->map(fn ($q) => $q->pivot->position + 1)->all());
        $question72 = $import->drafts()->where('number', 72)->first();
        $this->assertSame(30, $import->drafts()->where('status', 'added')->count());
        $this->assertNull($question72->question_id);

        // Importing the same paper again and adding to ANOTHER test reuses the questions
        $again = $service->start('question-imports/paper.pdf', 'paper.pdf', ImportMethod::Pdf, null, null);
        $other = $this->test('Second test');
        $counts = $service->commit($again, $again->drafts()->where('number', '<=', 30)->pluck('id')->all(), $other);

        $this->assertSame(['added' => 0, 'reused' => 30], ['added' => $counts['added'], 'reused' => $counts['reused']]);
        $this->assertSame(30, Question::count(), 'no duplicate questions in the bank');
        $this->assertSame(30, $other->questions()->count());

        // The correct answer and formula text survived the trip
        $saved = Question::whereHas('options', fn ($o) => $o->where('body', '9'))->where('body', 'like', '%∛729%')->first();
        $this->assertNull($saved, 'Q72 was not in the first 30');
        $rest = $service->commit($import, $import->drafts()->where('status', 'pending')->pluck('id')->all(), $test);
        $this->assertSame(70, $rest['added']);
        $q72 = Question::where('body', 'like', '%∛729%')->first();
        $this->assertSame(4, $q72->options->firstWhere('is_correct', true)->position);
        $this->assertSame(100, $test->questions()->count());
    }

    public function test_drafts_with_errors_are_blocked_until_fixed(): void
    {
        Storage::fake('local');
        $path = $this->xlsx([
            ['No', 'Question', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct (A-D)'],
            [1, 'Good question', 'a', 'b', 'c', 'd', 'B'],
            [2, 'No answer given', 'a', 'b', 'c', 'd', ''],
            [3, 'Only three options', 'a', 'b', 'c', '', 'A'],
        ]);
        $import = app(QuestionImportService::class)->start($path, 'q.xlsx', ImportMethod::Excel, null, null);
        $drafts = $import->drafts;

        $this->assertSame(['ok', 'error', 'error'], $drafts->map->severity()->all());
        $counts = app(QuestionImportService::class)->commit($import, $drafts->pluck('id')->all(), null);

        $this->assertSame(['added' => 1, 'blocked' => 2], ['added' => $counts['added'], 'blocked' => $counts['blocked']]);
        $this->assertSame(1, Question::count());
    }

    public function test_test_limit_of_200_questions_is_respected(): void
    {
        Storage::fake('local');
        $rows = [['No', 'Question', 'A', 'B', 'C', 'D', 'Answer']];
        foreach (range(1, 205) as $n) {
            $rows[] = [$n, "Question number {$n}", 'w', 'x', 'y', 'z', 'A'];
        }
        $import = app(QuestionImportService::class)->start($this->xlsx($rows), 'big.xlsx', ImportMethod::Excel, null, null);
        $test = $this->test();

        $counts = app(QuestionImportService::class)->commit($import, $import->drafts()->pluck('id')->all(), $test);

        $this->assertSame(200, $test->questions()->count());
        $this->assertSame(5, $counts['over_limit']);
        $this->assertSame(5, $import->drafts()->where('status', 'pending')->count(), 'the rest stay in the list for another test');
    }

    // ------------------------------------------------------------------ Excel

    public function test_excel_reader_understands_loose_headers_latex_and_answer_forms(): void
    {
        Storage::fake('local');
        $path = $this->xlsx([
            ['Q No.', 'Question Text', 'Option A', 'Option B', 'Option C', 'Option D', 'Correct Answer', 'Solution', 'Level', 'Section'],
            [1, 'Evaluate $\\int_0^1 x\\,dx$', '$1$', '$\\tfrac12$', '$0$', '$2$', 'b', 'Because $\\tfrac{x^2}{2}$', 'Medium', 'Maths'],
            [2, 'Pick the second one', 'one', 'two', 'three', 'four', 'two', null, 'hard', null],
            [3, 'Third <b>question</b> & more', 'a', 'b', 'c', 'd', '3', null, null, null],
        ]);

        $result = app(ExcelParser::class)->parse(Storage::disk('local')->path($path));

        $this->assertCount(3, $result->questions);
        [$a, $b, $c] = $result->questions;
        $this->assertSame('<p>Evaluate $\\int_0^1 x\\,dx$</p>', $a['body']);   // LaTeX untouched
        $this->assertSame(2, $a['correct']);
        $this->assertSame('medium', $a['difficulty']);
        $this->assertSame('Maths', $a['part']);
        $this->assertSame(2, $b['correct'], 'the answer can be given as the text of the option');
        $this->assertSame('hard', $b['difficulty']);
        $this->assertSame(3, $c['correct']);
        $this->assertStringContainsString('&lt;b&gt;question&lt;/b&gt; &amp; more', $c['body']); // user text can't inject HTML
    }

    public function test_the_downloadable_template_can_be_uploaded_back(): void
    {
        Storage::fake('local');
        $template = (new \App\Services\Imports\ExcelTemplate)->build();
        Storage::disk('local')->put('question-imports/template.xlsx', file_get_contents($template));
        @unlink($template);

        $import = app(QuestionImportService::class)->start('question-imports/template.xlsx', 'template.xlsx', ImportMethod::Excel, null, null);

        $this->assertSame('ready', $import->status, (string) $import->error);
        $this->assertSame(2, $import->drafts()->count());
        $this->assertSame(['ok', 'ok'], $import->drafts->map->severity()->all());
        $this->assertSame(2, $import->drafts[0]->correct);
        $this->assertSame('easy', $import->drafts[0]->difficulty);
        $this->assertStringContainsString('\\int_0^1', $import->drafts[1]->body);
    }

    public function test_excel_without_required_columns_fails_clearly(): void
    {
        Storage::fake('local');
        $path = $this->xlsx([['Foo', 'Bar'], ['x', 'y']]);

        $import = app(QuestionImportService::class)->start($path, 'bad.xlsx', ImportMethod::Excel, null, null);

        $this->assertSame('failed', $import->status);
        $this->assertStringContainsString('column', $import->error);
    }

    // ------------------------------------------------------------------ AI reader (the API call is replaced)

    public function test_ai_reader_splits_into_overlapping_ranges_dedupes_and_flags_text_that_differs_from_the_pdf(): void
    {
        $this->needsPdfTool();
        config(['services.gemini.key' => 'test-key', 'services.ai.pages_per_request' => 4]);

        // The "AI" answers from what the automatic reader found, so the test needs no network.
        $truth = app(PdfRuleParser::class)->parse($this->pdf)->questions;
        $parser = new class(app(PdfTool::class), app(ImportValidator::class), app(AiReaderManager::class), $truth) extends AiPdfParser
        {
            public array $calls = [];

            public function __construct(PdfTool $tool, ImportValidator $validator, AiReaderManager $ai, private array $truth)
            {
                parent::__construct($tool, $validator, $ai);
            }

            protected function ask(string $pdfBase64, int $ownPages, bool $hasExtraPage): array
            {
                $first = count($this->calls) * 4 + 1;
                $this->calls[] = [$first, $ownPages, $hasExtraPage, strlen($pdfBase64) > 1000];
                $last = $first + $ownPages - 1 + ($hasExtraPage ? 1 : 0);

                $questions = [];
                foreach ($this->truth as $q) {
                    if ($q['page'] < $first || $q['page'] > $last) {
                        continue; // not in the slice that was sent
                    }
                    $options = array_map(fn ($i) => html_entity_decode($q["option_{$i}"]), [1, 2, 3, 4]);
                    if ($q['number'] === 50) {
                        $options[0] = 'completely invented words that are not in the document';
                    }
                    $questions[] = [
                        'number' => $q['number'], 'part' => $q['part'], 'body' => $q['body'], 'options' => $options,
                        'correct' => $q['number'] === 60 ? ($q['correct'] % 4) + 1 : $q['correct'], // the "AI" gets Q60's answer wrong
                        'explanation' => $q['explanation'], 'difficulty' => $q['difficulty'],
                        'page' => $q['page'] - $first + 1, 'note' => $q['number'] === 70 ? 'figure needed' : null,
                    ];
                }

                return ['questions' => $questions, 'truncated' => false];
            }
        };

        $result = $parser->parse($this->pdf);

        $this->assertCount(12, $parser->calls, '45 pages in ranges of 4');
        $this->assertTrue($parser->calls[0][2], 'a range that is not the last gets one extra page');
        $this->assertFalse(end($parser->calls)[2], 'the last range has no extra page');
        $this->assertCount(100, $result->questions, 'questions repeated on overlap pages are kept once');
        $this->assertSame(range(1, 100), array_column($result->questions, 'number'));

        $byNumber = array_column($result->questions, null, 'number');
        $messages = fn (int $n) => implode(' | ', array_column($byNumber[$n]['warnings'], 'message'));
        $this->assertStringContainsString('Option A does not match the PDF text', $messages(50));
        $this->assertStringContainsString('AI note: figure needed', $messages(70));
        $this->assertSame('', $messages(51), 'untouched questions have no warnings');
        $this->assertTrue($byNumber[72]['_verified'], 'answers are still checked against the answer key');

        // A wrong answer is caught twice over: by the PDF's own "Answer:" lines and by the answer key table
        $this->assertStringContainsString('“Answer:” line says', $messages(60));
        $this->assertSame('error', collect($byNumber[60]['warnings'])->pluck('level')->first());
        $this->assertSame(['error'], collect($byNumber[60]['warnings'])->pluck('level')->unique()->values()->all());
        $this->assertContains('Read by Gemini (gemini-3.8-flash).', $result->notes);
    }

    public function test_ai_reader_needs_an_api_key(): void
    {
        config(['services.gemini.key' => null, 'services.openai.key' => null, 'services.anthropic.key' => null]);

        $this->expectExceptionMessage('GEMINI_API_KEY');
        app(AiPdfParser::class)->parse($this->pdf);
    }

    // ------------------------------------------------------------------

    /** Writes a spreadsheet to the fake private disk and returns its disk path. */
    private function xlsx(array $rows): string
    {
        $path = 'question-imports/test-'.uniqid().'.xlsx';
        Storage::disk('local')->put($path, '');
        $writer = new Writer;
        $writer->openToFile(Storage::disk('local')->path($path));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $path;
    }
}
