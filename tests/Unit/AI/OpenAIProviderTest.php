<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\AI;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WpAiAgent\AI\Providers\OpenAIProvider;
use WpAiAgent\Tools\ToolDefinition;

final class OpenAIProviderTest extends TestCase
{
    private OpenAIProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->provider = new OpenAIProvider(apiKey: 'sk-test-key', model: 'gpt-4o');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testFormatToolsUsesOpenAIFormat(): void
    {
        $tool = ToolDefinition::make('create_page', 'Creates a page');

        $result = $this->provider->formatTools([$tool]);

        $this->assertCount(1, $result);
        $this->assertSame('function', $result[0]['type']);
        $this->assertSame('create_page', $result[0]['function']['name']);
        $this->assertArrayHasKey('parameters', $result[0]['function']);
    }

    public function testSendMessageSuccessEndTurn(): void
    {
        $openaiResponse = [
            'choices' => [[
                'message' => ['role' => 'assistant', 'content' => 'Done!'],
                'finish_reason' => 'stop',
            ]],
        ];

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn(['response' => ['code' => 200], 'body' => json_encode($openaiResponse)]);
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn(json_encode($openaiResponse));

        $result = $this->provider->sendMessage([['role' => 'user', 'content' => 'Hi']]);

        $this->assertSame('end_turn', $result['stop_reason']);
        $this->assertSame('text', $result['content'][0]['type']);
        $this->assertSame('Done!', $result['content'][0]['text']);
    }

    public function testSendMessageWithToolCalls(): void
    {
        $openaiResponse = [
            'choices' => [[
                'message' => [
                    'role' => 'assistant',
                    'content' => null,
                    'tool_calls' => [[
                        'id' => 'call_abc',
                        'type' => 'function',
                        'function' => [
                            'name' => 'create_page',
                            'arguments' => '{"title":"Test Page"}',
                        ],
                    ]],
                ],
                'finish_reason' => 'tool_calls',
            ]],
        ];

        Functions\expect('wp_remote_post')->once()
            ->andReturn(['response' => ['code' => 200], 'body' => json_encode($openaiResponse)]);
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn(json_encode($openaiResponse));

        $result = $this->provider->sendMessage([['role' => 'user', 'content' => 'Create a page']]);

        $this->assertSame('tool_use', $result['stop_reason']);
        $this->assertCount(1, $result['content']);
        $this->assertSame('tool_use', $result['content'][0]['type']);
        $this->assertSame('call_abc', $result['content'][0]['id']);
        $this->assertSame('create_page', $result['content'][0]['name']);
        $this->assertSame(['title' => 'Test Page'], $result['content'][0]['input']);
    }

    public function testSendMessageIncludesAuthorizationHeader(): void
    {
        $openaiResponse = ['choices' => [['message' => ['content' => 'OK'], 'finish_reason' => 'stop']]];
        $capturedArgs = null;

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturnUsing(function (string $url, array $args) use (&$capturedArgs, $openaiResponse) {
                $capturedArgs = $args;
                return ['response' => ['code' => 200], 'body' => json_encode($openaiResponse)];
            });
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn(json_encode($openaiResponse));

        $this->provider->sendMessage([['role' => 'user', 'content' => 'Hi']]);

        $this->assertSame('Bearer sk-test-key', $capturedArgs['headers']['Authorization']);
    }

    public function testSendMessageIncludesToolsInPayload(): void
    {
        $openaiResponse = ['choices' => [['message' => ['content' => 'OK'], 'finish_reason' => 'stop']]];
        $capturedBody = null;

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturnUsing(function (string $url, array $args) use (&$capturedBody, $openaiResponse) {
                $capturedBody = json_decode($args['body'], true);
                return ['response' => ['code' => 200], 'body' => json_encode($openaiResponse)];
            });
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn(json_encode($openaiResponse));

        $tools = [ToolDefinition::make('my_tool', 'Does something')];
        $this->provider->sendMessage([['role' => 'user', 'content' => 'Hi']], $tools);

        $this->assertArrayHasKey('tools', $capturedBody);
        $this->assertSame('function', $capturedBody['tools'][0]['type']);
        $this->assertSame('my_tool', $capturedBody['tools'][0]['function']['name']);
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
        $this->expectExceptionMessage('OpenAI API error: HTTP 500');

        $this->provider->sendMessage([['role' => 'user', 'content' => 'Hi']]);
    }

    public function testRateLimitRetriesWithBackoff(): void
    {
        $openaiResponse = ['choices' => [['message' => ['content' => 'OK'], 'finish_reason' => 'stop']]];
        $callCount = 0;

        Functions\when('wp_remote_post')->alias(function () use (&$callCount) {
            $callCount++;
            return [];
        });
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->alias(function () use (&$callCount) {
            return $callCount <= 1 ? 429 : 200;
        });
        Functions\when('wp_remote_retrieve_body')->justReturn(json_encode($openaiResponse));
        Functions\when('sleep')->justReturn(0);

        $result = $this->provider->sendMessage([['role' => 'user', 'content' => 'Hi']]);

        $this->assertSame(2, $callCount);
        $this->assertSame('end_turn', $result['stop_reason']);
    }

    public function testNormalizesTextAndToolCallsInSingleResponse(): void
    {
        $openaiResponse = [
            'choices' => [[
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Creating page now',
                    'tool_calls' => [[
                        'id' => 'call_1',
                        'type' => 'function',
                        'function' => ['name' => 'create_page', 'arguments' => '{"title":"Home"}'],
                    ]],
                ],
                'finish_reason' => 'tool_calls',
            ]],
        ];

        Functions\expect('wp_remote_post')->once()
            ->andReturn(['response' => ['code' => 200], 'body' => json_encode($openaiResponse)]);
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->once()->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->once()->andReturn(json_encode($openaiResponse));

        $result = $this->provider->sendMessage([['role' => 'user', 'content' => 'Go']]);

        $this->assertSame('tool_use', $result['stop_reason']);
        $this->assertCount(2, $result['content']);
        $this->assertSame('text', $result['content'][0]['type']);
        $this->assertSame('tool_use', $result['content'][1]['type']);
    }
}
