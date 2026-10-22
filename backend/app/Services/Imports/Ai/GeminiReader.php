<?php

namespace App\Services\Imports\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Google Gemini, through the Interactions API (https://ai.google.dev/api/interactions-api).
 * The PDF is sent inline, so Gemini sees the page images as well as the text: good for formulas.
 * Key: GEMINI_API_KEY. Model: GEMINI_MODEL (default gemini-3.8-flash; gemini-3.5-flash-lite is cheaper).
 */
class GeminiReader implements AiReader
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/interactions';

    public function label(): string
    {
        return 'Gemini ('.config('services.gemini.model').')';
    }

    public function read(string $pdfBase64, string $prompt, array $schema): array
    {
        $response = Http::withHeaders(['x-goog-api-key' => config('services.gemini.key')])
            ->timeout(600)
            ->retry(3, 3000, fn ($e) => $e instanceof ConnectionException || ($e instanceof RequestException && in_array($e->response->status(), [429, 500, 502, 503, 504], true)), throw: true)
            ->post(self::ENDPOINT, [
                'model' => config('services.gemini.model'),
                'input' => [
                    ['type' => 'document', 'data' => $pdfBase64, 'mime_type' => 'application/pdf'],
                    ['type' => 'text', 'text' => $prompt],
                ],
                'response_format' => ['type' => 'text', 'mime_type' => 'application/json', 'schema' => $this->simplify($schema)],
                'generation_config' => ['max_output_tokens' => 32768, 'thinking_level' => 'low'],
            ]);

        return $this->parse($response->json() ?? []);
    }

    /** Reads the reply. Accepts the Interactions shape (steps → model_output → text) and the older candidates shape. */
    public function parse(array $json): array
    {
        if (in_array($json['status'] ?? null, ['failed', 'cancelled'], true)) {
            throw new RuntimeException('Gemini could not read this part: '.($json['error']['message'] ?? $json['status']));
        }

        $text = '';
        foreach ($json['steps'] ?? [] as $step) {
            if (($step['type'] ?? '') === 'model_output') {
                foreach ($step['content'] ?? [] as $part) {
                    if (($part['type'] ?? '') === 'text') {
                        $text .= $part['text'] ?? '';
                    }
                }
            }
        }
        if ($text === '') {
            $text = $json['output_text'] ?? $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
        }

        return [
            'text' => $text,
            'truncated' => ($json['status'] ?? null) === 'incomplete' || ($json['candidates'][0]['finishReason'] ?? null) === 'MAX_TOKENS',
        ];
    }

    /** Gemini accepts a subset of JSON Schema: drop keywords it does not support. */
    public function simplify(array $schema): array
    {
        unset($schema['additionalProperties']);

        if (isset($schema['enum']) && is_array($schema['type'] ?? null) && in_array('null', $schema['type'], true)) {
            $schema['enum'] = array_values(array_filter($schema['enum'], fn ($v) => $v !== null)); // "null" is already allowed by the type
        }
        foreach (['properties', 'items'] as $key) {
            if (isset($schema[$key]) && is_array($schema[$key])) {
                $schema[$key] = $key === 'items' ? $this->simplify($schema[$key]) : array_map(fn ($child) => is_array($child) ? $this->simplify($child) : $child, $schema[$key]);
            }
        }

        return $schema;
    }
}
