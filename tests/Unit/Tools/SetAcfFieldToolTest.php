<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Tools;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WpAiAgent\Tools\Implementations\SetAcfFieldTool;
use WpAiAgent\Tools\ToolDefinition;

final class SetAcfFieldToolTest extends TestCase
{
    private SetAcfFieldTool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->tool = new SetAcfFieldTool();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testGetName(): void
    {
        $this->assertSame('set_acf_field', $this->tool->getName());
    }

    public function testGetDefinitionReturnsToolDefinition(): void
    {
        $def = $this->tool->getDefinition();

        $this->assertInstanceOf(ToolDefinition::class, $def);
        $this->assertSame('set_acf_field', $def->name);
    }

    public function testExecuteReturnsErrorWhenAcfNotActive(): void
    {
        Functions\when('function_exists')->justReturn(false);

        $result = $this->tool->execute([
            'post_id' => 1,
            'field_key' => 'field_abc',
            'value' => 'test',
        ]);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('ACF Pro is not active', $result->message);
    }

    public function testExecuteSuccess(): void
    {
        Functions\expect('sanitize_text_field')->once()->andReturnArg(0);
        Functions\expect('update_field')->once()->with('field_abc', 'hello', 42)->andReturn(true);
        Functions\when('function_exists')->justReturn(true);

        $result = $this->tool->execute([
            'post_id' => 42,
            'field_key' => 'field_abc',
            'value' => 'hello',
        ]);

        $this->assertTrue($result->success);
        $this->assertSame(42, $result->data['post_id']);
        $this->assertSame('field_abc', $result->data['field_key']);
    }

    public function testExecuteReturnsErrorOnFailure(): void
    {
        Functions\when('sanitize_text_field')->returnArg();
        Functions\expect('update_field')->once()->andReturn(false);
        Functions\when('function_exists')->justReturn(true);

        $result = $this->tool->execute([
            'post_id' => 1,
            'field_key' => 'field_xyz',
            'value' => 'test',
        ]);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Failed to set ACF field', $result->message);
    }
}
