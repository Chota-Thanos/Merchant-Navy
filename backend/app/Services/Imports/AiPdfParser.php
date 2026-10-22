<?php

namespace App\Services\Imports;

use App\Services\Imports\Ai\AiReaderManager;
use App\Services\Pdf\PdfTool;
use Throwable;

/**
 * Reads questions from ANY PDF layout with an AI model that can look at the PDF (Gemini, OpenAI or Claude:
 * see Ai/AiReaderManager). The model sees the page images as well as the text, so stacked fractions, roots,
 * vector arrows and other typeset maths are understood and returned as LaTeX.
 *
 * How it stays accurate:
 *  - The PDF is cut into small page ranges. Each range is sent with one extra page, so a question that
 *    runs over a page break is read whole; only questions that START in the range are taken.
 *  - The model must answer in a fixed JSON format, so the reply is always readable.
 *  - Text is copied exactly, never reworded; maths is written as LaTeX between $ signs.
 *  - Every question is then checked against the PDF itself: its words must exist in the PDF's text,
 *    and its correct answer must match the PDF's own "Answer:" line.
 *  - The usual checks run afterwards too (4 options, answer key, duplicates…).
 */
class AiPdfParser implements QuestionParser
{
    private const SCHEMA = [
        'type' => 'object',
        'additionalProperties' => false,
        'required' => ['questions'],
        'properties' => [
            'questions' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['number', 'part', 'body', 'options', 'correct', 'explanation', 'difficulty', 'page', 'note'],
                    'properties' => [
                        'number' => ['type' => ['integer', 'null'], 'description' => 'Question number exactly as printed, null if none'],
                        'part' => ['type' => ['string', 'null'], 'description' => 'Section or topic name if the paper has one, e.g. "Part 2" or "Topic 1"'],
                        'body' => ['type' => 'string', 'description' => 'The question text as simple HTML (<p>, <br>, <strong>, <em>, <sub>, <sup>) with maths as LaTeX between $ signs'],
                        'options' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'The four options in order A, B, C, D, without their labels; maths as LaTeX between $ signs'],
                        'correct' => ['type' => ['integer', 'null'], 'description' => '1 to 4 for the correct option, null if the paper does not give the answer'],
                        'explanation' => ['type' => ['string', 'null'], 'description' => 'The worked solution if the paper has one, as simple HTML with LaTeX maths, else null'],
                        'difficulty' => ['type' => ['string', 'null'], 'enum' => ['easy', 'medium', 'hard', null]],
                        'page' => ['type' => 'integer', 'description' => 'Page of the PDF I sent (1 = first page) where the question starts'],
                        'note' => ['type' => ['string', 'null'], 'description' => 'Anything the reviewer must know: a figure/diagram the question depends on, unreadable text, an unclear answer. null if nothing'],
                    ],
                ],
            ],
        ],
    ];

    private const ALLOWED_HTML = '<p><br><strong><b><em><i><u><sub><sup><ul><ol><li><table><thead><tbody><tr><th><td>';

    public function __construct(private PdfTool $tool, private ImportValidator $validator, private AiReaderManager $ai) {}

    public function parse(string $path, ?callable $progress = null): ParseResult
    {
        $reader = $this->ai->driver(); // fails with a clear message when no key is set

        $doc = $this->tool->extract($path);
        $lines = collect($doc['pages'])->flatMap(fn ($p) => array_map(fn ($l) => $l + ['page' => $p['number']], $p['lines']))->all();
        [$answerKey] = DocumentFacts::answerKey($lines);
        $pageText = collect($doc['pages'])->mapWithKeys(fn ($p) => [$p['number'] => $this->words(implode(' ', array_column($p['lines'], 'text')))])->all();

        $result = new ParseResult(
            expected: DocumentFacts::expectedCount($lines, $doc['title'] ?? ''),
            answerKey: $answerKey,
            notes: ['Read by '.$reader->label().'.'],
        );

        $step = max(1, (int) config('services.ai.pages_per_request'));
        $total = (int) $doc['pageCount'];
        $byNumber = [];

        for ($first = 1; $first <= $total; $first += $step) {
            $last = min($total, $first + $step - 1);
            foreach ($this->readRange($path, $first, $last, $total, $pageText, $result) as $draft) {
                $key = $draft['number'] ?? md5($draft['body']);
                $byNumber[$key] ??= $draft; // overlap pages can show a question twice: keep the first
            }
            $progress && $progress((int) round($last / $total * 100));
        }

        $result->questions = array_values($byNumber);
        $this->checkAnswerLines($result, $lines);

        if (! $result->questions) {
            $result->notes[] = 'The AI did not find any questions in this PDF.';
        }
        $result->notes = $this->validator->check($result);

        return $result;
    }

    // ------------------------------------------------------------------

    /**
     * Questions that START on pages $first..$last. If the answer is cut off (too long), the range is split in two.
     *
     * @return array<int,array> drafts
     */
    private function readRange(string $path, int $first, int $last, int $total, array $pageText, ParseResult $result, int $depth = 0): array
    {
        $extra = $last < $total ? 1 : 0; // one more page so the last question can be finished
        $slice = tempnam(sys_get_temp_dir(), 'ai').'.pdf';

        try {
            $this->tool->split($path, $slice, $first, $last + $extra);
            $reply = $this->ask(base64_encode((string) file_get_contents($slice)), $last - $first + 1, (bool) $extra);
        } catch (Throwable $e) {
            report($e);
            $result->notes[] = "Pages {$first}–{$last} could not be read by the AI ({$e->getMessage()}). Re-run the import, or add those questions by hand.";

            return [];
        } finally {
            @unlink($slice);
        }

        if ($reply['truncated'] && $first < $last && $depth < 3) {
            $mid = intdiv($first + $last, 2);

            return [
                ...$this->readRange($path, $first, $mid, $total, $pageText, $result, $depth + 1),
                ...$this->readRange($path, $mid + 1, $last, $total, $pageText, $result, $depth + 1),
            ];
        }
        if ($reply['truncated']) {
            $result->notes[] = "Pages {$first}–{$last}: the AI's answer was cut off, so some questions may be missing.";
        }

        $drafts = [];
        foreach ($reply['questions'] as $q) {
            $page = $first + max(0, min((int) ($q['page'] ?? 1), $last - $first + 1 + $extra) - 1);
            $drafts[] = $this->toDraft($q, $page, $pageText, $first, $last + $extra);
        }

        return $drafts;
    }

    /** One request to the AI. Overridden in tests. @return array{questions: array, truncated: bool} */
    protected function ask(string $pdfBase64, int $ownPages, bool $hasExtraPage): array
    {
        $reply = $this->ai->driver()->read($pdfBase64, $this->prompt($ownPages, $hasExtraPage), self::SCHEMA);
        $data = json_decode($reply['text'], true);

        return [
            'questions' => is_array($data['questions'] ?? null) ? $data['questions'] : [],
            'truncated' => $reply['truncated'] || $data === null,
        ];
    }

    private function prompt(int $ownPages, bool $hasExtraPage): string
    {
        $extra = $hasExtraPage ? ' (the last page of the PDF is only there so you can finish a question that continues onto it; do not output questions that start on that last page)' : '';

        return <<<TXT
You are transcribing multiple-choice exam questions from the attached PDF into structured data. Look at the page images as well as the text: formulas are often typeset in ways that plain text loses.

Which questions to output: the PDF has {$ownPages} page(s) to read{$extra}. Output every question that STARTS on those pages. If the very first page begins with the end of a question that started on an earlier page, skip that partial question.

Rules, in order of importance:
1. Copy the text EXACTLY as printed. Never reword, correct, shorten, translate or solve anything. Keep every number, unit and symbol.
2. Write ALL mathematics as LaTeX between dollar signs: inline \$x^2\$, on its own line \$\$\\frac{a}{b}\$\$. Read formulas from the page image and reproduce their real structure:
   - stacked fractions: \\frac{16}{3}; roots: \\sqrt{x}, \\sqrt[3]{x}; the root bar covers everything under it
   - powers and subscripts: x^{2}, F_{1}, A_{x}, 10^{-3}; Greek letters: \\theta, \\lambda, \\Delta, \\pi
   - vectors: a bold letter or an arrow over a letter is a vector: \\vec{A} (or \\mathbf{A} if it is printed bold); unit vectors with a hat: \\hat{i}, \\hat{j}, \\hat{k}, \\hat{n}
   - magnitude |\\vec{A}|, dot product \\vec{A}\\cdot\\vec{B}, cross product \\vec{A}\\times\\vec{B}, degrees ^\\circ, sums \\sum, integrals \\int_{a}^{b}, limits, matrices, chemical formulas \\mathrm{H_2SO_4}
   - units go in \\text{}: 12\\,\\text{m/s}. Ordinary words stay plain text, outside the dollar signs.
3. Each question has exactly four options; return them in order without the letters (a)/(b)/A./1. etc. Options are plain text with LaTeX allowed, no HTML.
4. "correct" is the option the PDF marks or states as the answer (1 for a/A, 2 for b/B...). If the PDF gives no answer for a question, use null. Never work out the answer yourself.
5. "explanation" is the worked solution or reasoning the PDF prints for that question (keep every step, written with LaTeX), as simple HTML. Leave out lines that only say where to revise. null if there is none.
6. If a question depends on a figure, graph or table you cannot turn into text, still output the question and describe the problem in "note".
7. Do not output instructions pages, tables of contents, answer-key tables, headings or anything that is not a question.
TXT;
    }

    /** Turns the AI's answer for one question into a draft, and compares it with the PDF's own text. */
    private function toDraft(array $q, int $page, array $pageText, int $first, int $lastWithExtra): array
    {
        $draft = ParseResult::blank();
        $draft['number'] = isset($q['number']) ? (int) $q['number'] : null;
        $draft['part'] = $q['part'] ?? null;
        $draft['page'] = $page;
        $draft['body'] = $this->html((string) ($q['body'] ?? ''));
        $options = array_values($q['options'] ?? []);
        foreach (range(1, 4) as $n) {
            $draft["option_{$n}"] = isset($options[$n - 1]) ? htmlspecialchars(trim((string) $options[$n - 1]), ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
        }
        if (count($options) !== 4) {
            $draft['warnings'][] = ['level' => 'error', 'message' => 'The AI returned '.count($options).' options instead of 4.'];
        }
        $draft['correct'] = in_array($q['correct'] ?? null, [1, 2, 3, 4], true) ? $q['correct'] : null;
        $draft['explanation'] = filled($q['explanation'] ?? null) ? $this->html((string) $q['explanation']) : null;
        $draft['difficulty'] = in_array($q['difficulty'] ?? null, ['easy', 'medium', 'hard'], true) ? $q['difficulty'] : null;

        if (filled($q['note'] ?? null)) {
            $draft['warnings'][] = ['level' => 'warning', 'message' => 'AI note: '.trim($q['note'])];
        }

        // Compare with the text the PDF really contains (catches reworded or invented text)
        $available = [];
        foreach (range($first, $lastWithExtra) as $p) {
            $available += array_flip($pageText[$p] ?? []);
        }
        $fields = ['the question text' => $draft['body']] + collect(range(1, 4))->mapWithKeys(fn ($i) => ['option '.chr(64 + $i) => (string) $draft["option_{$i}"]])->all();
        foreach ($fields as $name => $text) {
            $words = $this->words(strip_tags($text));
            if (count($words) >= 2 && $available) {
                $found = count(array_filter($words, fn ($w) => isset($available[$w])));
                if ($found / count($words) < 0.8) {
                    $draft['warnings'][] = ['level' => 'warning', 'message' => ucfirst($name).' does not match the PDF text word for word. Compare it with the PDF before adding.'];
                }
            }
        }

        return $draft;
    }

    /**
     * Cross-check that does not depend on how formulas are typeset: the letters on the PDF's own
     * "Answer: (c) …" lines, in order, must equal the AI's correct answers, in order.
     */
    private function checkAnswerLines(ParseResult $result, array $lines): void
    {
        $letters = [];
        foreach ($lines as $line) {
            if (preg_match('/^answer\s*[:\-–]\s*\(?([a-dA-D])\)?(?:\s|$)/iu', trim($line['text']), $m)) {
                $letters[] = ord(strtolower($m[1])) - 96;
            }
        }
        if (! $letters) {
            return;
        }
        if (count($letters) !== count($result->questions)) {
            $result->notes[] = 'The PDF has '.count($letters).' “Answer:” lines but '.count($result->questions).' questions were read, so the answers could not be cross-checked one by one.';

            return;
        }

        $mismatch = 0;
        foreach ($result->questions as $i => &$draft) {
            if ($draft['correct'] !== $letters[$i]) {
                $mismatch++;
                $draft['warnings'][] = ['level' => 'error', 'message' => 'The PDF’s “Answer:” line says '.chr(64 + $letters[$i]).' but the AI read '.($draft['correct'] ? chr(64 + $draft['correct']) : 'no answer').'. Check which is right.'];
            } else {
                $draft['_verified'] = true;
            }
        }
        unset($draft);
        if (! $mismatch) {
            $result->notes[] = 'All '.count($letters).' answers match the PDF’s own “Answer:” lines.';
        }
    }

    /** Keeps simple formatting tags only. */
    private function html(string $html): string
    {
        $clean = strip_tags(trim($html), self::ALLOWED_HTML);

        return str_starts_with($clean, '<') ? $clean : '<p>'.$clean.'</p>';
    }

    /**
     * The words and numbers that can be compared whatever the formula layout: words of 3+ letters and
     * numbers of 2+ digits, lower case, with LaTeX commands dropped and maths letters normalised.
     * (Single letters and digits are skipped: a typeset fraction or power scatters them across the page.)
     *
     * @return array<int,string>
     */
    private function words(string $text): array
    {
        $text = preg_replace('/\\\\(?:[a-zA-Z]+|.)/u', ' ', html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_KC) ?: $text; // 𝜆 → λ, 𝑡 → t
        }
        preg_match_all('/\p{L}{3,}|\p{N}{2,}/u', mb_strtolower($text), $m);

        return $m[0];
    }
}
