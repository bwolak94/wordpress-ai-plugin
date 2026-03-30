<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\AI;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WpAiAgent\AI\Providers\ClaudeProvider;
use WpAiAgent\Tools\ToolDefinition;

final class ClaudeProviderTest extends TestCase
{
    private ClaudeProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->provider = new ClaudeProvider(apiKey: 'sk-test-key', model: 'claude-opus-4-5');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testFormatToolsCallsToAnthropicFormat(): void
    {
        $tool1 = ToolDefinition::make('create_page', 'Creates a page');
        $tool2 = ToolDefinition::make('set_field', 'Sets a field');

        $result = $this->provider->formatTools([$tool1, $tool2]);

        $this->assertCount(2, $result);
        $this->assertSame('create_page', $result[0]['name']);
        $this->assertSame('set_field', $result[1]['name']);
        $this->assertArrayHasKey('input_schema', $result[0]);
        $this->assertArrayHasKey('input_schema', $result[1]);
    }

    public function testSendMessageSuccess(): void
    {
        $apiResponse = ['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Hello']]];

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn(['response' => ['code' => 200], 'body' => json_encode($apiResponse)]);
        Functions\expect('is_wp_error')
            ->once()
            ->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')
            ->once()
            ->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->andReturn(json_encode($apiResponse));

        $result = $this->provider->sendMessage([['role' => 'user', 'content' => 'Hi']]);

        $this->assertSame('end_turn', $result['stop_reason']);
    }

    public function testSendMessageIncludesToolsInPayload(): void
    {
        $apiResponse = ['stop_reason' => 'tool_use', 'content' => []];
        $capturedBody = null;

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturnUsing(function (string $url, array $args) use (&$capturedBody, $apiResponse) {
                $capturedBody = json_decode($args['body'], true);
                return ['response' => ['code' => 200], 'body' => json_encode($apiResponse)];
            });
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn(json_encode($apiResponse));

        $tools = [ToolDefinition::make('my_tool', 'Does something')];
        $this->provider->sendMessage([['role' => 'user', 'content' => 'Hi']], $tools);

        $this->assertArrayHasKey('tools', $capturedBody);
        $this->assertCount(1, $capturedBody['tools']);
        $this->assertSame('my_tool', $capturedBody['tools'][0]['name']);
    }

    public function testWpErrorThrowsRuntimeException(): void
    {
        $wpError = \Mockery::mock('WP_Error');
        $wpError->shouldReceive('get_error_message')->andReturn('Connection failed');

        Functions\expect('wp_remote_post')->once()->andReturn($wpError);
        Functions\expect('is_wp_error')->once()->andReturn(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection failed');

        $this->provider->sendMessage([['role' => 'user', 'content' => 'Hi']]);
    }

    public function testHttpErrorThrowsRuntimeException(): void
    {
        Functions\expect('wp_remote_post')->once()->andReturn([]);
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(500);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn('Internal Server Error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Claude API error: HTTP 500');

        $this->provider->sendMessage([['role' => 'user', 'content' => 'Hi']]);
    }

    public function testRateLimitRetriesWithBackoff(): void
    {
        $apiResponse = ['stop_reason' => 'end_turn', 'content' => []];
        $callCount = 0;

        Functions\when('wp_remote_post')->alias(function () use (&$callCount) {
            $callCount++;
            return [];
        });
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->alias(function () use (&$callCount) {
            // First call returns 429, second returns 200
            return $callCount <= 1 ? 429 : 200;
        });
        Functions\when('wp_remote_retrieve_body')->justReturn(json_encode($apiResponse));
        Functions\when('sleep')->justReturn(0);

        $result = $this->provider->sendMessage([['role' => 'user', 'content' => 'Hi']]);

        $this->assertSame(2, $callCount);
        $this->assertSame('end_turn', $result['stop_reason']);
    }

    public function testRateLimitExhaustsRetriesAndThrows(): void
    {
        Functions\when('wp_remote_post')->justReturn([]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(429);
        Functions\when('wp_remote_retrieve_body')->justReturn('Rate limited');
        Functions\when('sleep')->justReturn(0);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Claude API error: HTTP 429');

        $this->provider->sendMessage([['role' => 'user', 'content' => 'Hi']]);
    }
}
