<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Tools;

use PHPUnit\Framework\TestCase;
use WpAiAgent\Tools\ToolResult;

final class ToolResultTest extends TestCase
{
    public function testSuccessFactory(): void
    {
        $result = ToolResult::success('Page created', ['id' => 1]);

        $this->assertTrue($result->success);
        $this->assertSame('Page created', $result->message);
        $this->assertSame(['id' => 1], $result->data);
    }

    public function testSuccessDefaultData(): void
    {
        $result = ToolResult::success('ok');

        $this->assertSame([], $result->data);
    }

    public function testErrorFactory(): void
    {
        $result = ToolResult::error('Something failed');

        $this->assertFalse($result->success);
        $this->assertSame('Something failed', $result->message);
        $this->assertSame([], $result->data);
    }

    public function testToJsonReturnsValidJson(): void
    {
        $result = ToolResult::success('ok', ['id' => 1]);
        $json = $result->toJson();

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($decoded['success']);
        $this->assertSame('ok', $decoded['message']);
        $this->assertSame(['id' => 1], $decoded['data']);
    }

    public function testToJsonErrorResult(): void
    {
        $result = ToolResult::error('fail');
        $decoded = json_decode($result->toJson(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertFalse($decoded['success']);
        $this->assertSame('fail', $decoded['message']);
    }
}
