<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Tools;

use PHPUnit\Framework\TestCase;
use WpAiAgent\Tools\Contracts\ToolInterface;
use WpAiAgent\Tools\ToolDefinition;
use WpAiAgent\Tools\ToolRegistry;
use WpAiAgent\Tools\ToolResult;

final class ToolRegistryTest extends TestCase
{
    private ToolRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new ToolRegistry();
    }

    public function testRegisterReturnsFluentInterface(): void
    {
        $tool = $this->createToolStub('my_tool');

        $result = $this->registry->register($tool);

        $this->assertSame($this->registry, $result);
    }

    public function testHasReturnsTrueForRegisteredTool(): void
    {
        $this->registry->register($this->createToolStub('my_tool'));

        $this->assertTrue($this->registry->has('my_tool'));
        $this->assertFalse($this->registry->has('other_tool'));
    }

    public function testNamesReturnsRegisteredToolNames(): void
    {
        $this->registry
            ->register($this->createToolStub('tool_a'))
            ->register($this->createToolStub('tool_b'));

        $this->assertSame(['tool_a', 'tool_b'], $this->registry->names());
    }

    public function testDispatchUnknownToolReturnsError(): void
    {
        $result = $this->registry->dispatch('unknown_tool', []);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Unknown tool', $result->message);
    }

    public function testDispatchExecutesTool(): void
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('create_page');
        $tool->method('execute')
            ->with(['title' => 'Hello'])
            ->willReturn(ToolResult::success('Page created', ['id' => 1]));

        $this->registry->register($tool);
        $result = $this->registry->dispatch('create_page', ['title' => 'Hello']);

        $this->assertTrue($result->success);
        $this->assertSame('Page created', $result->message);
        $this->assertSame(['id' => 1], $result->data);
    }

    public function testDispatchCatchesThrowable(): void
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('bad_tool');
        $tool->method('execute')->willThrowException(new \RuntimeException('Boom'));

        $this->registry->register($tool);
        $result = $this->registry->dispatch('bad_tool', []);

        $this->assertFalse($result->success);
        $this->assertSame('Boom', $result->message);
    }

    public function testGetDefinitionsReturnsToolDefinitions(): void
    {
        $defA = ToolDefinition::make('tool_a', 'Tool A');
        $defB = ToolDefinition::make('tool_b', 'Tool B');

        $toolA = $this->createMock(ToolInterface::class);
        $toolA->method('getName')->willReturn('tool_a');
        $toolA->method('getDefinition')->willReturn($defA);

        $toolB = $this->createMock(ToolInterface::class);
        $toolB->method('getName')->willReturn('tool_b');
        $toolB->method('getDefinition')->willReturn($defB);

        $this->registry->register($toolA)->register($toolB);

        $definitions = $this->registry->getDefinitions();

        $this->assertCount(2, $definitions);
        $this->assertSame($defA, $definitions[0]);
        $this->assertSame($defB, $definitions[1]);
    }

    public function testLastRegistrationWinsOnDuplicateName(): void
    {
        $first = $this->createMock(ToolInterface::class);
        $first->method('getName')->willReturn('my_tool');
        $first->method('execute')->willReturn(ToolResult::success('first'));

        $second = $this->createMock(ToolInterface::class);
        $second->method('getName')->willReturn('my_tool');
        $second->method('execute')->willReturn(ToolResult::success('second'));

        $this->registry->register($first)->register($second);

        $result = $this->registry->dispatch('my_tool', []);
        $this->assertSame('second', $result->message);
        $this->assertSame(['my_tool'], $this->registry->names());
    }

    private function createToolStub(string $name): ToolInterface
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn($name);
        return $tool;
    }
}
