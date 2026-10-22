<?php

namespace App\Services\Imports;

use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

/**
 * Reads questions from an Excel (.xlsx) or CSV file, one question per row.
 * The first row holds the column names. Download the ready-made template from the admin panel.
 *
 * Columns (names are matched loosely, in any order):
 *   Question | Option A | Option B | Option C | Option D | Correct (A–D) | Explanation | Difficulty | Part | No
 * Maths: write LaTeX between dollar signs, e.g. $x^2$.
 */
class ExcelParser implements QuestionParser
{
    /** Column aliases, after lower-casing and removing spaces/punctuation. */
    private const COLUMNS = [
        'number' => ['no', 'number', 'qno', 'sno', 'srno', 'q', 'questionno', 'questionnumber'],
        'body' => ['question', 'questiontext', 'body', 'text', 'q'],
        'option_1' => ['optiona', 'a', 'option1', 'opta', 'choicea', '1'],
        'option_2' => ['optionb', 'b', 'option2', 'optb', 'choiceb', '2'],
        'option_3' => ['optionc', 'c', 'option3', 'optc', 'choicec', '3'],
        'option_4' => ['optiond', 'd', 'option4', 'optd', 'choiced', '4'],
        'correct' => ['correct', 'answer', 'correctanswer', 'ans', 'correctoption', 'key', 'rightanswer'],
        'explanation' => ['explanation', 'solution', 'sol', 'reason', 'answerexplanation'],
        'difficulty' => ['difficulty', 'level'],
        'part' => ['part', 'section', 'topic', 'group'],
    ];

    public function __construct(private ImportValidator $validator) {}

    public function parse(string $path, ?callable $progress = null): ParseResult
    {
        $rows = $this->rows($path);
        if (count($rows) < 2) {
            throw new RuntimeException('The file has no question rows. The first row must contain the column names.');
        }

        $map = $this->mapColumns(array_shift($rows));
        foreach (['body', 'option_1', 'option_2', 'option_3', 'option_4', 'correct'] as $required) {
            if (! isset($map[$required])) {
                throw new RuntimeException("The column for “{$required}” was not found. Use the template from the admin panel.");
            }
        }

        $result = new ParseResult;
        foreach ($rows as $i => $row) {
            $get = fn (string $field) => isset($map[$field]) ? trim((string) ($row[$map[$field]] ?? '')) : '';
            if ($get('body') === '' && $get('option_1') === '') {
                continue; // empty row
            }

            $draft = ParseResult::blank();
            $draft['number'] = ctype_digit($get('number')) ? (int) $get('number') : null;
            $draft['part'] = $get('part') ?: null;
            $draft['page'] = $i + 2; // spreadsheet row number
            $draft['body'] = $this->html($get('body'));
            foreach (range(1, 4) as $n) {
                $draft["option_{$n}"] = $get("option_{$n}") === '' ? null : $this->html($get("option_{$n}"), false);
            }
            $draft['correct'] = $this->correct($get('correct'), $draft);
            $draft['explanation'] = $get('explanation') === '' ? null : $this->html($get('explanation'));
            $draft['difficulty'] = $this->difficulty($get('difficulty'));
            $result->questions[] = $draft;
        }

        if (! $result->questions) {
            $result->notes[] = 'No question rows were found in the file.';
        }
        $result->notes = $this->validator->check($result);

        return $result;
    }

    /** @return array<int,array<int,mixed>> every row of the first sheet */
    private function rows(string $path): array
    {
        $reader = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'csv' ? new CsvReader : new XlsxReader;
        $reader->open($path);

        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = array_map(fn ($cell) => $cell instanceof \DateTimeInterface ? $cell->format('Y-m-d') : $cell, $row->toArray());
            }
            break; // first sheet only
        }
        $reader->close();

        return $rows;
    }

    /** @return array<string,int> field => column index */
    private function mapColumns(array $header): array
    {
        $map = [];
        foreach ($header as $index => $title) {
            // "Correct (A-D)" and "Difficulty (easy/medium/hard)" are the template's headers: ignore the bracket hints
            $key = preg_replace('/[^a-z0-9]/', '', strtolower(preg_replace('/\(.*?\)/', '', (string) $title)));
            foreach (self::COLUMNS as $field => $aliases) {
                if (in_array($key, $aliases, true) && ! isset($map[$field])) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    /** "B", "b", "2", or the full text of the right option. */
    private function correct(string $value, array $draft): ?int
    {
        $v = strtolower(trim($value, " ().\t"));
        if (in_array($v, ['a', 'b', 'c', 'd'], true)) {
            return ord($v) - 96;
        }
        if (in_array($v, ['1', '2', '3', '4'], true)) {
            return (int) $v;
        }
        foreach (range(1, 4) as $n) {
            if ($v !== '' && $this->validator->norm((string) $draft["option_{$n}"]) === $this->validator->norm($value)) {
                return $n;
            }
        }

        return null;
    }

    private function difficulty(string $value): ?string
    {
        return match (strtolower(substr($value, 0, 1))) {
            'e' => 'easy', 'm' => 'medium', 'h' => 'hard', default => null,
        };
    }

    /** Plain text → safe HTML. Line breaks are kept; LaTeX between $ signs is left alone. */
    private function html(string $text, bool $paragraph = true): string
    {
        $safe = nl2br(htmlspecialchars(trim($text), ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);

        return $paragraph ? "<p>{$safe}</p>" : $safe;
    }
}
