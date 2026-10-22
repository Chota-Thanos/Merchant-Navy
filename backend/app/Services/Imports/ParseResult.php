<?php

namespace App\Services\Imports;

/** What a parser found: the draft questions plus facts used to double-check them. */
class ParseResult
{
    /**
     * @param  array<int,array>  $questions  drafts, see blank()
     * @param  int|null  $expected  how many questions the file says it contains (if it says)
     * @param  array<int,string>  $answerKey  question number => 'a'..'d' from an answer-key page (if there is one)
     * @param  array<int,string>  $notes  messages about the file as a whole
     */
    public function __construct(
        public array $questions = [],
        public ?int $expected = null,
        public array $answerKey = [],
        public array $notes = [],
    ) {}

    /** A draft question with every key present. `_answer_text` is the answer line's text, used only for checking. */
    public static function blank(): array
    {
        return [
            'number' => null, 'part' => null, 'kind' => null, 'body' => '',
            'option_1' => null, 'option_2' => null, 'option_3' => null, 'option_4' => null,
            'correct' => null, 'explanation' => null, 'difficulty' => null,
            'page' => null, 'warnings' => [], '_answer_text' => null, '_verified' => null,
        ];
    }
}
