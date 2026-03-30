<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Tools;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WpAiAgent\Tools\Implementations\UploadMediaTool;
use WpAiAgent\Tools\ToolDefinition;

final class UploadMediaToolTest extends TestCase
{
    private UploadMediaTool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->tool = new UploadMediaTool();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testGetName(): void
    {
        $this->assertSame('upload_media', $this->tool->getName());
    }

    public function testGetDefinitionReturnsToolDefinition(): void
    {
        $def = $this->tool->getDefinition();

        $this->assertInstanceOf(ToolDefinition::class, $def);
        $this->assertSame('upload_media', $def->name);
        $this->assertContains('url', $def->inputSchema['required']);
    }

    public function testExecuteSuccess(): void
    {
        Functions\expect('esc_url_raw')->once()->andReturnArg(0);
        Functions\expect('download_url')->once()->andReturn('/tmp/image.jpg');
        Functions\expect('is_wp_error')->twice()->andReturn(false);
        Functions\expect('media_handle_sideload')->once()->andReturn(99);
        Functions\expect('wp_get_attachment_url')->once()->andReturn('https://example.com/image.jpg');

        $result = $this->tool->execute(['url' => 'https://example.com/image.jpg']);

        $this->assertTrue($result->success);
        $this->assertSame(99, $result->data['attachment_id']);
    }

    public function testExecuteDownloadFailure(): void
    {
        $wpError = \Mockery::mock('WP_Error');
        $wpError->shouldReceive('get_error_message')->andReturn('Download timeout');

        Functions\expect('esc_url_raw')->once()->andReturnArg(0);
        Functions\expect('download_url')->once()->andReturn($wpError);
        Functions\expect('is_wp_error')->once()->andReturn(true);

        $result = $this->tool->execute(['url' => 'https://example.com/bad.jpg']);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Download failed', $result->message);
    }

    public function testExecuteSideloadFailureCleansUpTmpFile(): void
    {
        $wpError = \Mockery::mock('WP_Error');
        $wpError->shouldReceive('get_error_message')->andReturn('Sideload failed');

        Functions\expect('esc_url_raw')->once()->andReturnArg(0);
        Functions\expect('download_url')->once()->andReturn('/tmp/tmpfile.jpg');
        Functions\expect('is_wp_error')
            ->twice()
            ->andReturn(false, true);
        Functions\expect('media_handle_sideload')->once()->andReturn($wpError);

        $result = $this->tool->execute(['url' => 'https://example.com/image.jpg']);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Media upload failed', $result->message);
    }
}
