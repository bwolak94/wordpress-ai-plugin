<?php

declare(strict_types=1);

namespace WpAiAgent\Agent;

final class AgentConfig
{
    public function __construct(
        public readonly string $model = 'claude-opus-4-5',
        public readonly int $maxTokens = 4096,
        public readonly int $maxRounds = 20,
    ) {}
}
