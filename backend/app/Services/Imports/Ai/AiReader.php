<?php

namespace App\Services\Imports\Ai;

/**
 * One AI service that can read a PDF and answer in a fixed JSON format.
 * Implementations: GeminiReader, OpenAiReader, AnthropicReader. Which one is used is decided by AiReaderManager.
 * To add a provider: implement this interface and add it to AiReaderManager::DRIVERS.
 */
interface AiReader
{
    /** For messages and logs, e.g. "Gemini (gemini-3.8-flash)". */
    public function label(): string;

    /**
     * Sends the PDF and the instructions; the answer must follow `$schema` (JSON Schema).
     *
     * @param  string  $pdfBase64  the PDF file, base64 encoded
     * @return array{text: string, truncated: bool} the raw JSON text, and whether the model ran out of output space
     */
    public function read(string $pdfBase64, string $prompt, array $schema): array;
}
