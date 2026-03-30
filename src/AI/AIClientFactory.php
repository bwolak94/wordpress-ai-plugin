<?php

declare(strict_types=1);

namespace WpAiAgent\AI;

use WpAiAgent\AI\Contracts\AIProviderInterface;
use WpAiAgent\AI\Providers\ClaudeProvider;
use WpAiAgent\AI\Providers\OpenAIProvider;

final class AIClientFactory
{
    private const OPENAI_MODELS = ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'o1', 'o3'];

    public static function create(?string $model = null): AIProviderInterface
    {
        $model = $model ?? get_option('wp_ai_agent_model', 'claude-opus-4-5');

        if (self::isOpenAIModel($model)) {
            return self::createOpenAI($model);
        }

        return self::createClaude($model);
    }

    private static function createClaude(string $model): ClaudeProvider
    {
        $apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';

        if (empty($apiKey)) {
            throw new \RuntimeException('ANTHROPIC_API_KEY is not configured');
        }

        return new ClaudeProvider(apiKey: $apiKey, model: $model);
    }

    private static function createOpenAI(string $model): OpenAIProvider
    {
        $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';

        if (empty($apiKey)) {
            throw new \RuntimeException('OPENAI_API_KEY is not configured');
        }

        return new OpenAIProvider(apiKey: $apiKey, model: $model);
    }

    private static function isOpenAIModel(string $model): bool
    {
        foreach (self::OPENAI_MODELS as $prefix) {
            if (str_starts_with($model, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
