<?php

declare(strict_types=1);

namespace WpAiAgent\AI;

use WpAiAgent\AI\Contracts\AIProviderInterface;
use WpAiAgent\AI\Providers\ClaudeProvider;

final class AIClientFactory
{
    public static function create(?string $model = null): AIProviderInterface
    {
        $apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';
        $model  = $model ?? get_option('wp_ai_agent_model', 'claude-opus-4-5');

        if (empty($apiKey)) {
            throw new \RuntimeException('ANTHROPIC_API_KEY is not configured');
        }

        return new ClaudeProvider(apiKey: $apiKey, model: $model);
    }
}
