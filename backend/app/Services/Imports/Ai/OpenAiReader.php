<?php

namespace App\Services\Imports\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OpenAI, through the Responses API. The PDF is sent as a base64 file, so the model sees page images and text.
 * Key: OPENAI_API_KEY. Model: OPENAI_MODEL (default gpt-5-mini; gpt-5-nano is cheaper).
 */
class OpenAiReader implements AiReader
{
    private const ENDPOINT = 'https://api.openai.com/v1/responses';

    public function label(): string
    {
        return 'OpenAI ('.config('services.openai.model').')';
    }

    public function read(string $pdfBase64, string $prompt, array $schema): array
    {
        $model = (string) config('services.openai.model');

        $body = [
            'model' => $model,
            'input' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'input_file', 'filename' => 'questions.pdf', 'file_data' => 'data:application/pdf;base64,'.$pdfBase64],
                    ['type' => 'input_text', 'text' => $prompt],
                ],
            ]],
            'text' => ['format' => ['type' => 'json_schema', 'name' => 'questions', 'strict' => true, 'schema' => $schema]],
            'max_output_tokens' => 32000,
        ];
        if (preg_match('/^(gpt-5|o\d)/', $model)) {
            $body['reasoning'] = ['effort' => 'low']; // reading, not reasoning: keep it fast and cheap
        }

        $response = Http::withToken((string) config('services.openai.key'))
            ->timeout(600)
            ->retry(3, 3000, fn ($e) => $e instanceof ConnectionException || ($e instanceof RequestException && in_array($e->response->status(), [429, 500, 502, 503, 504], true)), throw: true)
            ->post(self::ENDPOINT, $body);

        return $this->parse($response->json() ?? []);
    }

    public function parse(array $json): array
    {
        if (($json['status'] ?? null) === 'failed') {
            throw new RuntimeException('OpenAI could not read this part: '.($json['error']['message'] ?? 'failed'));
        }

        $text = '';
        foreach ($json['output'] ?? [] as $item) {
            if (($item['type'] ?? '') !== 'message') {
                continue;
            }
            foreach ($item['content'] ?? [] as $part) {
                if (($part['type'] ?? '') === 'refusal') {
                    throw new RuntimeException('OpenAI declined to read this part of the document.');
                }
                if (($part['type'] ?? '') === 'output_text') {
                    $text .= $part['text'] ?? '';
                }
            }
        }

        return [
            'text' => $text,
            'truncated' => ($json['status'] ?? null) === 'incomplete' && ($json['incomplete_details']['reason'] ?? null) === 'max_output_tokens',
        ];
    }
}
