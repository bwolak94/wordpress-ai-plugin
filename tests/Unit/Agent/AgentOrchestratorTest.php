<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Agent;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use WpAiAgent\Agent\AgentOrchestrator;
use WpAiAgent\AI\Contracts\AIProviderInterface;
use WpAiAgent\Tools\ToolRegistry;
use WpAiAgent\Tools\ToolResult;
use WpAiAgent\Tools\ToolDefinition;
use WpAiAgent\Tools\Contracts\ToolInterface;
use WpAiAgent\DTO\Brief;
use WpAiAgent\DTO\AgentResult;
use WpAiAgent\Events\EventBus;
use WpAiAgent\Events\ToolExecuted;
use WpAiAgent\Events\AgentFinished;

final class AgentOrchestratorTest extends TestCase
{
    private AIProviderInterface $ai;
    private ToolRegistry $registry;
    private EventBus $events;
    private AgentOrchestrator $orchestrator;

    /** @var object[] */
    private array $dispatchedEvents = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Monkey\Functions\when('do_action')->justReturn();

        $this->ai = $this->createMock(AIProviderInterface::class);
        $this->registry = new ToolRegistry();
        $this->events = new EventBus();
        $this->dispatchedEvents = [];

        $this->events->listen(ToolExecuted::class, function (ToolExecuted $e): void {
            $this->dispatchedEvents[] = $e;
        });
        $this->events->listen(AgentFinished::class, function (AgentFinished $e): void {
            $this->dispatchedEvents[] = $e;
        });

        $this->orchestrator = new AgentOrchestrator(
            $this->ai,
            $this->registry,
            $this->events,
        );
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testStopsOnEndTurn(): void
    {
        $brief = $this->createBrief();

        $this->ai->method('sendMessage')->willReturn([
            'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => 'Done.']],
        ]);

        $result = $this->orchestrator->run($brief);

        $this->assertTrue($result->success);
        $this->assertSame(1, $result->rounds);
        $this->assertEmpty($result->log);
        $this->assertEmpty($result->pages);

        $this->assertCount(1, $this->dispatchedEvents);
        $this->assertInstanceOf(AgentFinished::class, $this->dispatchedEvents[0]);
    }

    public function testStopsWhenNoToolUseBlocks(): void
    {
        $brief = $this->createBrief();

        $this->ai->method('sendMessage')->willReturn([
            'stop_reason' => 'max_tokens',
            'content' => [['type' => 'text', 'text' => 'Thinking...']],
        ]);

        $result = $this->orchestrator->run($brief);

        $this->assertTrue($result->success);
        $this->assertSame(1, $result->rounds);
    }

    public function testDispatchesToolAndAppendsResult(): void
    {
        $brief = $this->createBrief();

        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('get_acf_schema');
        $tool->method('getDefinition')->willReturn(ToolDefinition::make('get_acf_schema', 'Get ACF schema'));
        $tool->method('execute')->willReturn(ToolResult::success('Schema found', ['fields' => []]));
        $this->registry->register($tool);

        $callCount = 0;
        $this->ai->method('sendMessage')->willReturnCallback(
            function () use (&$callCount): array {
                $callCount++;
                if ($callCount === 1) {
                    return [
                        'stop_reason' => 'tool_use',
                        'content' => [
                            ['type' => 'tool_use', 'id' => 'call_1', 'name' => 'get_acf_schema', 'input' => ['group' => 'group_abc']],
                        ],
                    ];
                }
                return [
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => 'Done.']],
                ];
            }
        );

        $result = $this->orchestrator->run($brief);

        $this->assertSame(2, $result->rounds);
        $this->assertCount(1, $result->log);
        $this->assertStringContainsString('get_acf_schema', $result->log[0]);

        $this->assertCount(2, $this->dispatchedEvents);
        $this->assertInstanceOf(ToolExecuted::class, $this->dispatchedEvents[0]);
        $this->assertInstanceOf(AgentFinished::class, $this->dispatchedEvents[1]);

        $this->assertSame('get_acf_schema', $this->dispatchedEvents[0]->toolName);
    }

    public function testTracksCreatedPages(): void
    {
        $brief = $this->createBrief();

        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('create_page');
        $tool->method('getDefinition')->willReturn(ToolDefinition::make('create_page', 'Create page'));
        $tool->method('execute')->willReturn(ToolResult::success('Page created', ['post_id' => 42, 'title' => 'My Page']));
        $this->registry->register($tool);

        $callCount = 0;
        $this->ai->method('sendMessage')->willReturnCallback(
            function () use (&$callCount): array {
                $callCount++;
                if ($callCount === 1) {
                    return [
                        'stop_reason' => 'tool_use',
                        'content' => [
                            ['type' => 'tool_use', 'id' => 'call_1', 'name' => 'create_page', 'input' => ['title' => 'My Page']],
                        ],
                    ];
                }
                return [
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => 'Created page.']],
                ];
            }
        );

        $result = $this->orchestrator->run($brief);

        $this->assertCount(1, $result->pages);
        $this->assertSame(42, $result->pages[0]['post_id']);
    }

    public function testDoesNotTrackFailedCreatePage(): void
    {
        $brief = $this->createBrief();

        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('create_page');
        $tool->method('getDefinition')->willReturn(ToolDefinition::make('create_page', 'Create page'));
        $tool->method('execute')->willReturn(ToolResult::error('Failed to create page'));
        $this->registry->register($tool);

        $callCount = 0;
        $this->ai->method('sendMessage')->willReturnCallback(
            function () use (&$callCount): array {
                $callCount++;
                if ($callCount === 1) {
                    return [
                        'stop_reason' => 'tool_use',
                        'content' => [
                            ['type' => 'tool_use', 'id' => 'call_1', 'name' => 'create_page', 'input' => ['title' => 'Test']],
                        ],
                    ];
                }
                return ['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Failed.']]];
            }
        );

        $result = $this->orchestrator->run($brief);

        $this->assertEmpty($result->pages);
        $this->assertCount(1, $result->log);
    }

    public function testMaxRoundsGuard(): void
    {
        $brief = $this->createBrief();

        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('get_acf_schema');
        $tool->method('getDefinition')->willReturn(ToolDefinition::make('get_acf_schema', 'Get schema'));
        $tool->method('execute')->willReturn(ToolResult::success('OK'));
        $this->registry->register($tool);

        $this->ai->method('sendMessage')->willReturn([
            'stop_reason' => 'tool_use',
            'content' => [
                ['type' => 'tool_use', 'id' => 'call_1', 'name' => 'get_acf_schema', 'input' => []],
            ],
        ]);

        $result = $this->orchestrator->run($brief);

        $this->assertSame(20, $result->rounds);
        $this->assertCount(20, $result->log);
        $this->assertTrue($result->success);
    }

    public function testMultipleToolsInSingleResponse(): void
    {
        $brief = $this->createBrief();

        $createTool = $this->createMock(ToolInterface::class);
        $createTool->method('getName')->willReturn('create_page');
        $createTool->method('getDefinition')->willReturn(ToolDefinition::make('create_page', 'Create page'));
        $createTool->method('execute')->willReturn(ToolResult::success('Created', ['post_id' => 10]));

        $setFieldTool = $this->createMock(ToolInterface::class);
        $setFieldTool->method('getName')->willReturn('set_acf_field');
        $setFieldTool->method('getDefinition')->willReturn(ToolDefinition::make('set_acf_field', 'Set field'));
        $setFieldTool->method('execute')->willReturn(ToolResult::success('Field set'));

        $this->registry->register($createTool)->register($setFieldTool);

        $callCount = 0;
        $this->ai->method('sendMessage')->willReturnCallback(
            function () use (&$callCount): array {
                $callCount++;
                if ($callCount === 1) {
                    return [
                        'stop_reason' => 'tool_use',
                        'content' => [
                            ['type' => 'tool_use', 'id' => 'call_1', 'name' => 'create_page', 'input' => ['title' => 'Page']],
                            ['type' => 'tool_use', 'id' => 'call_2', 'name' => 'set_acf_field', 'input' => ['field' => 'hero']],
                        ],
                    ];
                }
                return ['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'All done.']]];
            }
        );

        $result = $this->orchestrator->run($brief);

        $this->assertCount(2, $result->log);
        $this->assertCount(1, $result->pages);

        $toolExecutedEvents = array_filter($this->dispatchedEvents, fn($e) => $e instanceof ToolExecuted);
        $this->assertCount(2, $toolExecutedEvents);
    }

    public function testEmitsToolExecutedForEveryToolCall(): void
    {
        $brief = $this->createBrief();

        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('get_acf_schema');
        $tool->method('getDefinition')->willReturn(ToolDefinition::make('get_acf_schema', 'Get schema'));
        $tool->method('execute')->willReturn(ToolResult::success('OK'));
        $this->registry->register($tool);

        $callCount = 0;
        $this->ai->method('sendMessage')->willReturnCallback(
            function () use (&$callCount): array {
                $callCount++;
                if ($callCount <= 2) {
                    return [
                        'stop_reason' => 'tool_use',
                        'content' => [
                            ['type' => 'tool_use', 'id' => "call_{$callCount}", 'name' => 'get_acf_schema', 'input' => []],
                        ],
                    ];
                }
                return ['stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => 'Done.']]];
            }
        );

        $result = $this->orchestrator->run($brief);

        $toolExecutedEvents = array_filter($this->dispatchedEvents, fn($e) => $e instanceof ToolExecuted);
        $this->assertCount(2, $toolExecutedEvents);
        $this->assertSame(3, $result->rounds);
    }

    public function testAgentFinishedEventContainsResult(): void
    {
        $brief = $this->createBrief();

        $this->ai->method('sendMessage')->willReturn([
            'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => 'Nothing to do.']],
        ]);

        $result = $this->orchestrator->run($brief);

        $finishedEvents = array_filter($this->dispatchedEvents, fn($e) => $e instanceof AgentFinished);
        $this->assertCount(1, $finishedEvents);

        $capturedEvent = array_values($finishedEvents)[0];
        $this->assertSame($result->success, $capturedEvent->result->success);
        $this->assertSame($result->rounds, $capturedEvent->result->rounds);
    }

    private function createBrief(): Brief
    {
        \Brain\Monkey\Functions\when('sanitize_textarea_field')->returnArg();
        \Brain\Monkey\Functions\when('sanitize_text_field')->returnArg();

        return Brief::fromArray([
            'documentation' => 'Test product docs',
            'goals' => 'Create a landing page',
            'target_url' => 'https://example.com',
        ]);
    }
}
