<?php

declare(strict_types=1);

namespace WpAiAgent\AI\Contracts;

use WpAiAgent\Tools\ToolDefinition;

interface AIProviderInterface
{
    /**
     * Send a conversation with tools to the AI provider.
     * Returns the raw provider response array.
     *
     * @param array<array{role:string, content:string|array}> $messages
     * @param ToolDefinition[] $tools
     * @return array{stop_reason:string, content:array}
     */
    public function sendMessage(array $messages, array $tools = []): array;

    /**
     * Convert ToolDefinition[] to the provider's native format.
     * Anthropic and OpenAI have different schemas.
     *
     * @param ToolDefinition[] $tools
     * @return array
     */
    public function formatTools(array $tools): array;
}
