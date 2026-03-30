<?php

declare(strict_types=1);

namespace WpAiAgent\AI\Providers;

use WpAiAgent\AI\Contracts\AIProviderInterface;
use WpAiAgent\Tools\ToolDefinition;

final class ClaudeProvider implements AIProviderInterface
{
    private const API_URL    = 'https://api.anthropic.com/v1/messages';
    private const MAX_RETRY  = 3;
    private const RETRY_BASE = 1.5;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'claude-opus-4-5',
        private readonly int $maxTokens = 4096,
    ) {}

    public function sendMessage(array $messages, array $tools = []): array
    {
        $payload = [
            'model'      => $this->model,
            'max_tokens' => $this->maxTokens,
            'messages'   => $messages,
        ];

        if (!empty($tools)) {
            $payload['tools'] = $this->formatTools($tools);
        }

        return $this->sendWithRetry($payload);
    }

    public function formatTools(array $tools): array
    {
        return array_map(
            fn(ToolDefinition $t) => $t->toAnthropicFormat(),
            $tools
        );
    }

    private function sendWithRetry(array $payload, int $attempt = 0): array
    {
        $response = wp_remote_post(self::API_URL, [
            'headers' => [
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'body'    => json_encode($payload, JSON_THROW_ON_ERROR),
            'timeout' => 120,
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code === 429 && $attempt < self::MAX_RETRY) {
            sleep((int) (self::RETRY_BASE ** $attempt));
            return $this->sendWithRetry($payload, $attempt + 1);
        }

        if ($code !== 200) {
            $body = wp_remote_retrieve_body($response);
            throw new \RuntimeException("Claude API error: HTTP {$code} — {$body}");
        }

        return json_decode(
            wp_remote_retrieve_body($response),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
