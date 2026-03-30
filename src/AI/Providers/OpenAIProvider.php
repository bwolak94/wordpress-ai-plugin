<?php

declare(strict_types=1);

namespace WpAiAgent\AI\Providers;

use WpAiAgent\AI\Contracts\AIProviderInterface;
use WpAiAgent\Tools\ToolDefinition;

final class OpenAIProvider implements AIProviderInterface
{
    private const API_URL    = 'https://api.openai.com/v1/chat/completions';
    private const MAX_RETRY  = 3;
    private const RETRY_BASE = 1.5;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'gpt-4o',
        private readonly int $maxTokens = 4096,
    ) {}

    public function sendMessage(array $messages, array $tools = []): array
    {
        $payload = [
            'model'      => $this->model,
            'max_tokens' => $this->maxTokens,
            'messages'   => $this->convertMessages($messages),
        ];

        if (!empty($tools)) {
            $payload['tools'] = $this->formatTools($tools);
        }

        $response = $this->sendWithRetry($payload);

        return $this->normalizeResponse($response);
    }

    public function formatTools(array $tools): array
    {
        return array_map(
            fn(ToolDefinition $t) => $t->toOpenAIFormat(),
            $tools
        );
    }

    /**
     * Convert Anthropic-style messages to OpenAI chat format.
     * Handles tool_result blocks which OpenAI expects as role: "tool".
     *
     * @return array<array{role:string, content:string|null, tool_calls?:array, tool_call_id?:string}>
     */
    private function convertMessages(array $messages): array
    {
        $converted = [];

        foreach ($messages as $msg) {
            $role    = $msg['role'];
            $content = $msg['content'];

            if ($role === 'assistant' && is_array($content)) {
                $converted[] = $this->convertAssistantBlocks($content);
                continue;
            }

            if ($role === 'user' && is_array($content)) {
                foreach ($content as $block) {
                    if (($block['type'] ?? '') === 'tool_result') {
                        $converted[] = [
                            'role'         => 'tool',
                            'tool_call_id' => $block['tool_use_id'],
                            'content'      => is_string($block['content']) ? $block['content'] : json_encode($block['content']),
                        ];
                    }
                }
                continue;
            }

            $converted[] = ['role' => $role, 'content' => is_string($content) ? $content : json_encode($content)];
        }

        return $converted;
    }

    /**
     * Convert Anthropic assistant content blocks (text + tool_use) to OpenAI format.
     */
    private function convertAssistantBlocks(array $blocks): array
    {
        $text      = '';
        $toolCalls = [];

        foreach ($blocks as $block) {
            if ($block['type'] === 'text') {
                $text .= $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                $toolCalls[] = [
                    'id'       => $block['id'],
                    'type'     => 'function',
                    'function' => [
                        'name'      => $block['name'],
                        'arguments' => json_encode($block['input'], JSON_THROW_ON_ERROR),
                    ],
                ];
            }
        }

        $msg = ['role' => 'assistant', 'content' => $text ?: null];

        if (!empty($toolCalls)) {
            $msg['tool_calls'] = $toolCalls;
        }

        return $msg;
    }

    /**
     * Normalize OpenAI response to match the Anthropic format expected by AgentOrchestrator.
     *
     * @return array{stop_reason:string, content:array}
     */
    private function normalizeResponse(array $response): array
    {
        $choice     = $response['choices'][0] ?? [];
        $message    = $choice['message'] ?? [];
        $finishReason = $choice['finish_reason'] ?? 'stop';

        $content = [];

        if (!empty($message['content'])) {
            $content[] = ['type' => 'text', 'text' => $message['content']];
        }

        foreach ($message['tool_calls'] ?? [] as $toolCall) {
            $content[] = [
                'type'  => 'tool_use',
                'id'    => $toolCall['id'],
                'name'  => $toolCall['function']['name'],
                'input' => json_decode($toolCall['function']['arguments'], true, 512, JSON_THROW_ON_ERROR),
            ];
        }

        $stopReason = match ($finishReason) {
            'tool_calls' => 'tool_use',
            'stop'       => 'end_turn',
            'length'     => 'max_tokens',
            default      => $finishReason,
        };

        return [
            'stop_reason' => $stopReason,
            'content'     => $content,
        ];
    }

    private function sendWithRetry(array $payload, int $attempt = 0): array
    {
        $response = wp_remote_post(self::API_URL, [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
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
            throw new \RuntimeException("OpenAI API error: HTTP {$code} — {$body}");
        }

        return json_decode(
            wp_remote_retrieve_body($response),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
