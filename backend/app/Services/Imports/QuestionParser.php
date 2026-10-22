<?php

namespace App\Services\Imports;

/**
 * Turns a file into draft questions. One implementation per kind of file / method:
 *   PdfRuleParser (automatic PDF reader), AiPdfParser (Claude), ExcelParser.
 * Every draft is an array with the keys listed in ParseResult::blank().
 */
interface QuestionParser
{
    /** @param  callable(int $percent): void|null  $progress  called while a long parse runs */
    public function parse(string $path, ?callable $progress = null): ParseResult;
}
