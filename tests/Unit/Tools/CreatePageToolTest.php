<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Tools;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WpAiAgent\Tools\Implementations\CreatePageTool;
use WpAiAgent\Tools\ToolDefinition;

final class CreatePageToolTest extends TestCase
{
    private CreatePageTool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->tool = new CreatePageTool();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testGetName(): void
    {
        $this->assertSame('create_page', $this->tool->getName());
    }

    public function testGetDefinitionReturnsToolDefinition(): void
    {
        $def = $this->tool->getDefinition();

        $this->assertInstanceOf(ToolDefinition::class, $def);
        $this->assertSame('create_page', $def->name);
        $this->assertContains('title', $def->inputSchema['required']);
    }

    public function testExecuteSuccess(): void
    {
        Functions\expect('sanitize_text_field')->once()->andReturnArg(0);
        Functions\expect('wp_kses_post')->once()->andReturnArg(0);
        Functions\expect('sanitize_title')->once()->andReturn('test-page');
        Functions\expect('wp_insert_post')->once()->andReturn(42);
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('get_edit_post_link')->once()->andReturn('https://example.com/wp-admin/post.php?post=42');

        $result = $this->tool->execute(['title' => 'Test Page', 'content' => '<p>Hello</p>']);

        $this->assertTrue($result->success);
        $this->assertSame(42, $result->data['post_id']);
    }

    public function testExecuteWpError(): void
    {
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_kses_post')->returnArg();
        Functions\when('sanitize_title')->justReturn('test');

        $wpError = \Mockery::mock('WP_Error');
        $wpError->shouldReceive('get_error_message')->andReturn('Insert failed');

        Functions\expect('wp_insert_post')->once()->andReturn($wpError);
        Functions\expect('is_wp_error')->once()->andReturn(true);

        $result = $this->tool->execute(['title' => 'Test']);

        $this->assertFalse($result->success);
        $this->assertSame('Insert failed', $result->message);
    }

    public function testExecuteSanitizesInputs(): void
    {
        $capturedArgs = null;

        Functions\expect('sanitize_text_field')->once()->andReturn('Clean Title');
        Functions\expect('wp_kses_post')->once()->andReturn('<p>Clean</p>');
        Functions\expect('sanitize_title')->once()->andReturn('clean-slug');
        Functions\expect('wp_insert_post')->once()->andReturnUsing(function (array $args) use (&$capturedArgs) {
            $capturedArgs = $args;
            return 1;
        });
        Functions\expect('is_wp_error')->once()->andReturn(false);
        Functions\expect('get_edit_post_link')->once()->andReturn('');

        $this->tool->execute([
            'title' => '<script>bad</script>',
            'content' => '<script>bad</script>',
            'slug' => 'my-slug',
        ]);

        $this->assertSame('Clean Title', $capturedArgs['post_title']);
        $this->assertSame('<p>Clean</p>', $capturedArgs['post_content']);
        $this->assertSame('clean-slug', $capturedArgs['post_name']);
    }

    public function testExecuteDefaultsToDraftStatus(): void
    {
        $capturedArgs = null;

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_kses_post')->returnArg();
        Functions\when('sanitize_title')->justReturn('test');
        Functions\expect('wp_insert_post')->once()->andReturnUsing(function (array $args) use (&$capturedArgs) {
            $capturedArgs = $args;
            return 1;
        });
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('get_edit_post_link')->justReturn('');

        $this->tool->execute(['title' => 'Test']);

        $this->assertSame('draft', $capturedArgs['post_status']);
    }
}
