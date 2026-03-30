<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Agent;

use PHPUnit\Framework\TestCase;
use WpAiAgent\Agent\AgentConfig;

final class AgentConfigTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $config = new AgentConfig();

        $this->assertSame('claude-opus-4-5', $config->model);
        $this->assertSame(4096, $config->maxTokens);
        $this->assertSame(20, $config->maxRounds);
    }

    public function testCustomValues(): void
    {
        $config = new AgentConfig(
            model: 'claude-sonnet-4-5',
            maxTokens: 8192,
            maxRounds: 10,
        );

        $this->assertSame('claude-sonnet-4-5', $config->model);
        $this->assertSame(8192, $config->maxTokens);
        $this->assertSame(10, $config->maxRounds);
    }
}
