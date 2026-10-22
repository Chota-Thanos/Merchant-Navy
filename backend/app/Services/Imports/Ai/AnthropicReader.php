<?php

namespace App\Services\Imports\Ai;

use Anthropic\Client;
use RuntimeException;

/**
 * Anthropic Claude (official PHP SDK). The most accurate but also the most expensive option.
 * Key: ANTHROPIC_API_KEY. Model: ANTHROPIC_MODEL (default claude-opus-5-5; claude-sonnet-5-5 is about half the price).
 */
class AnthropicReader implements AiReader
{
    public function label(): string
    {
        return 'Claude ('.config('services.anthropic.model').')';
    }

    public function read(string $pdfBase64, string $prompt, array $schema): array
    {
        $client = new Client(apiKey: config('services.anthropic.key'));

        $message = $client->messages->create(
            model: config('services.anthropic.model'),
            maxTokens: 16000,
            messages: [[
                'role' => 'user',
                'content' => [
                    ['type' => 'document', 'source' => ['type' => 'base64', 'mediaType' => 'application/pdf', 'data' => $pdfBase64]],
                    ['type' => 'text', 'text' => $prompt],
                ],
            ]],
            outputConfig: ['format' => ['type' => 'json_schema', 'schema' => $schema]],
        );

        if ($message->stopReason === 'refusal') {
            throw new RuntimeException('Claude declined to read this part of the document.');
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return ['text' => $text, 'truncated' => $message->stopReason === 'max_tokens'];
    }
}
