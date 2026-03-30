<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\AI;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WpAiAgent\AI\AIClientFactory;
use WpAiAgent\AI\Providers\ClaudeProvider;

final class AIClientFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Ensure the constant exists for tests that need it
        if (!defined('ANTHROPIC_API_KEY')) {
            define('ANTHROPIC_API_KEY', 'sk-ant-test');
        }
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testCreateReturnsClaudeProvider(): void
    {
        Functions\when('get_option')->justReturn('claude-opus-4-5');

        $provider = AIClientFactory::create();

        $this->assertInstanceOf(ClaudeProvider::class, $provider);
    }

    public function testCreateUsesProvidedModel(): void
    {
        Functions\when('get_option')->justReturn('claude-opus-4-5');

        $provider = AIClientFactory::create('claude-sonnet-4-5');

        $this->assertInstanceOf(ClaudeProvider::class, $provider);
    }

    public function testCreateThrowsWhenApiKeyEmpty(): void
    {
        // Test the empty-key branch by overriding defined() to return false
        Functions\when('defined')->justReturn(false);
        Functions\when('get_option')->justReturn('claude-opus-4-5');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ANTHROPIC_API_KEY is not configured');

        AIClientFactory::create();
    }
}
