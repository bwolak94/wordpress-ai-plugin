<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Tools;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WpAiAgent\Tools\Implementations\SetMenuItemTool;
use WpAiAgent\Tools\ToolDefinition;

final class SetMenuItemToolTest extends TestCase
{
    private SetMenuItemTool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->tool = new SetMenuItemTool();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testGetName(): void
    {
        $this->assertSame('set_menu_item', $this->tool->getName());
    }

    public function testGetDefinitionReturnsToolDefinition(): void
    {
        $def = $this->tool->getDefinition();

        $this->assertInstanceOf(ToolDefinition::class, $def);
        $this->assertSame('set_menu_item', $def->name);
    }

    public function testExecuteSuccess(): void
    {
        Functions\expect('get_nav_menu_locations')->once()->andReturn(['primary' => 5]);
        Functions\expect('sanitize_text_field')->andReturnArg(0);
        Functions\expect('wp_update_nav_menu_item')->once()->andReturn(101);
        Functions\expect('is_wp_error')->once()->andReturn(false);

        $result = $this->tool->execute([
            'menu_location' => 'primary',
            'post_id' => 42,
        ]);

        $this->assertTrue($result->success);
        $this->assertSame(101, $result->data['menu_item_id']);
        $this->assertSame(5, $result->data['menu_id']);
    }

    public function testExecuteMenuLocationNotFound(): void
    {
        Functions\expect('get_nav_menu_locations')->once()->andReturn(['primary' => 5]);
        Functions\expect('sanitize_text_field')->andReturnArg(0);

        $result = $this->tool->execute([
            'menu_location' => 'nonexistent',
            'post_id' => 42,
        ]);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('not found', $result->message);
    }

    public function testExecuteWpError(): void
    {
        $wpError = \Mockery::mock('WP_Error');
        $wpError->shouldReceive('get_error_message')->andReturn('Menu error');

        Functions\expect('get_nav_menu_locations')->once()->andReturn(['primary' => 5]);
        Functions\expect('sanitize_text_field')->andReturnArg(0);
        Functions\expect('wp_update_nav_menu_item')->once()->andReturn($wpError);
        Functions\expect('is_wp_error')->once()->andReturn(true);

        $result = $this->tool->execute([
            'menu_location' => 'primary',
            'post_id' => 42,
        ]);

        $this->assertFalse($result->success);
        $this->assertSame('Menu error', $result->message);
    }
}
