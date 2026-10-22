<?php

namespace App\Services\Imports;

/**
 * Facts a question paper states about itself, used to double-check any parser's work:
 * how many questions it has, and its answer key (usually a table on the last page).
 * Works on the lines produced by tools/pdf_tool.py (each line has a 'text').
 */
class DocumentFacts
{
    /** "Number of questions: 100", "Test of 100 Questions", or a number at the end of the title. */
    public static function expectedCount(array $lines, string $title = ''): ?int
    {
        foreach (array_slice($lines, 0, 80) as $line) {
            if (preg_match('/number of questions\s*[:\-]\s*(\d+)/i', $line['text'], $m)
                || preg_match('/\b(?:test|paper|quiz) of (\d+) questions\b/i', $line['text'], $m)) {
                return (int) $m[1];
            }
        }

        return preg_match('/\b(\d+)\s*$/', $title, $m) ? (int) $m[1] : null;
    }

    /**
     * Finds an "Answer key" heading and reads "1 (c) 21 (a) 41 (c) …" after it.
     *
     * @return array{0: array<int,string>, 1: array<int,array>} [number => letter, the lines before the key]
     */
    public static function answerKey(array $lines): array
    {
        foreach ($lines as $i => $line) {
            if (preg_match('/^answer\s*key\b/i', trim($line['text']))) {
                return [self::readKey(array_slice($lines, $i + 1)), array_slice($lines, 0, $i)];
            }
        }

        return [[], $lines];
    }

    /** @param  array<int,array>  $keyLines */
    public static function readKey(array $keyLines): array
    {
        $text = implode(' ', array_map(fn ($l) => $l['text'], $keyLines));
        preg_match_all('/(?<!\d)(\d{1,3})\s*\(?\s*([a-dA-D])\s*\)?(?=\s|$)/u', $text, $matches, PREG_SET_ORDER);

        $key = [];
        foreach ($matches as $m) {
            $key[(int) $m[1]] ??= strtolower($m[2]);
        }

        return $key;
    }
}
