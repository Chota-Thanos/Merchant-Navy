<?php

namespace App\Services\Imports;

use App\Models\Question;

/**
 * Double-checks parsed questions so mistakes are visible before anything is saved.
 *   error   = the question is incomplete; it cannot be added until the admin fixes it
 *   warning = probably fine, but the admin should look at it
 * Also compares against the file's own answer key and the existing question bank.
 */
class ImportValidator
{
    /** Checks every draft and the file as a whole. Returns messages about the whole file. */
    public function check(ParseResult $result): array
    {
        $notes = $result->notes;
        $drafts = &$result->questions;

        foreach ($drafts as &$draft) {
            $this->checkOne($draft, $result->answerKey);
        }
        unset($draft);

        $this->markExistingQuestions($drafts);

        // Whole file
        $numbers = array_filter(array_column($drafts, 'number'));
        if ($numbers) {
            $missing = array_diff(range(min($numbers), max($numbers)), $numbers);
            if ($missing) {
                $notes[] = 'Question numbers missing from the file: '.$this->ranges($missing).'. They may be skipped in the PDF or not read correctly.';
            }
            $duplicates = array_keys(array_filter(array_count_values($numbers), fn ($n) => $n > 1));
            if ($duplicates) {
                $notes[] = 'The same question number appears more than once: '.implode(', ', array_map(fn ($n) => "Q{$n}", $duplicates)).'.';
            }
        }
        if ($result->expected !== null && $result->expected !== count($drafts)) {
            $notes[] = "The document says it has {$result->expected} questions but ".count($drafts).' were found. Check the list before adding.';
        }
        if ($result->answerKey) {
            $checked = count(array_filter($drafts, fn ($d) => $d['_verified'] === true));
            $notes[] = $checked === count($drafts)
                ? "All {$checked} answers match the answer key at the end of the document."
                : "{$checked} of ".count($drafts).' answers were confirmed against the answer key.';
        }

        return $notes;
    }

    /** Fresh warnings for one draft, e.g. after the admin edited it (the file-level answer key no longer applies). */
    public function recheck(array $draft): array
    {
        $draft['warnings'] = [];
        $draft['_answer_text'] = null;
        $this->checkOne($draft, []);

        return $draft['warnings'];
    }

    private function checkOne(array &$d, array $answerKey): void
    {
        $w = [];
        $add = function (string $level, string $message) use (&$w) {
            $w[] = ['level' => $level, 'message' => $message];
        };

        if (mb_strlen(trim(strip_tags($d['body']))) < 4) {
            $add('error', 'The question text is missing or too short.');
        }
        foreach (range(1, 4) as $i) {
            if (trim((string) $d["option_{$i}"]) === '') {
                $add('error', 'Option '.chr(64 + $i).' is missing.');
            }
        }
        if (! in_array($d['correct'], [1, 2, 3, 4], true)) {
            $add('error', 'The correct answer was not found.');
        }

        // Identical options: compare with punctuation kept, so "A/cos θ" and "Acos θ" are different
        $options = array_map(fn ($i) => $this->exact((string) $d["option_{$i}"]), range(1, 4));
        if (count(array_unique(array_filter($options))) < count(array_filter($options))) {
            $add('warning', 'Two options look identical.');
        }

        // Answer line text vs the option it points at (the answer line may wrap, so a start of the option is enough)
        if ($d['_answer_text'] && in_array($d['correct'], [1, 2, 3, 4], true)) {
            $picked = $this->norm((string) $d['option_'.$d['correct']]);
            $said = $this->norm($d['_answer_text']);
            if ($said !== '' && $picked !== '' && ! str_starts_with($picked, $said)) {
                $add('warning', "The answer line says “{$d['_answer_text']}” but that is not the text of option ".chr(64 + $d['correct']).'.');
            }
        }

        // Answer key at the end of the document
        $d['_verified'] = null;
        if ($d['number'] && isset($answerKey[$d['number']]) && in_array($d['correct'], [1, 2, 3, 4], true)) {
            $keyLetter = strtolower($answerKey[$d['number']]);
            if ($keyLetter === chr(96 + $d['correct'])) {
                $d['_verified'] = true;
            } else {
                $d['_verified'] = false;
                $add('error', 'The answer key at the end of the document says '.strtoupper($keyLetter).' but the question says '.chr(64 + $d['correct']).'. Check which is right.');
            }
        }

        // Characters that usually mean a symbol was not read
        $all = $d['body'].' '.implode(' ', array_map(fn ($i) => (string) $d["option_{$i}"], range(1, 4))).' '.$d['explanation'];
        if (preg_match('/[\x{FFFD}\x{E000}-\x{F8FF}\x{25A1}\x{25A0}]/u', $all)) {
            $add('warning', 'Some symbols may not have been read correctly (unknown characters found).');
        }
        if (collect(range(1, 4))->contains(fn ($i) => mb_strlen((string) $d["option_{$i}"]) > 400)) {
            $add('warning', 'An option is very long: two options may have been merged.');
        }

        $d['warnings'] = [...$d['warnings'], ...$w];
    }

    /** Warns when the same question (same text and options) is already in the question bank. */
    private function markExistingQuestions(array &$drafts): void
    {
        $existing = Question::whereIn('body', array_column($drafts, 'body'))->with('options')->get()->groupBy('body');

        foreach ($drafts as &$d) {
            foreach ($existing->get($d['body'], []) as $question) {
                $same = $question->options->pluck('body')->map(fn ($b) => $this->norm((string) $b))->all()
                    === array_map(fn ($i) => $this->norm((string) $d["option_{$i}"]), range(1, 4));
                if ($same) {
                    $d['warnings'][] = ['level' => 'warning', 'message' => "Already in the question bank (#{$question->id}). It will be reused, not duplicated."];
                    break;
                }
            }
        }
    }

    /** Lowercase without spaces, tags kept out but every symbol kept: "A/B" ≠ "AB". */
    private function exact(string $text): string
    {
        return preg_replace('/\s+/u', '', mb_strtolower(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8')));
    }

    /** Lowercase, no spaces or punctuation: for comparing texts. */
    public function norm(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');

        return preg_replace('/[\s\p{P}]+/u', '', mb_strtolower($text));
    }

    /** [1,2,3,7] → "1–3, 7" */
    private function ranges(array $numbers): string
    {
        sort($numbers);
        $out = [];
        for ($i = 0; $i < count($numbers); $i++) {
            $start = $numbers[$i];
            while (isset($numbers[$i + 1]) && $numbers[$i + 1] === $numbers[$i] + 1) {
                $i++;
            }
            $out[] = $start === $numbers[$i] ? "Q{$start}" : "Q{$start}–Q{$numbers[$i]}";
        }

        return implode(', ', array_slice($out, 0, 12)).(count($out) > 12 ? '…' : '');
    }
}
