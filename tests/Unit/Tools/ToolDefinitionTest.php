<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Tools;

use PHPUnit\Framework\TestCase;
use WpAiAgent\Tools\ToolDefinition;

final class ToolDefinitionTest extends TestCase
{
    public function testMakeCreatesDefaultSchema(): void
    {
        $def = ToolDefinition::make('create_page', 'Creates a WP page');

        $this->assertSame('create_page', $def->name);
        $this->assertSame('Creates a WP page', $def->description);
        $this->assertSame([
            'type'       => 'object',
            'properties' => [],
            'required'   => [],
        ], $def->inputSchema);
    }

    public function testWithSchemaReturnsNewInstance(): void
    {
        $original = ToolDefinition::make('tool', 'desc');
        $schema = [
            'type'       => 'object',
            'properties' => ['title' => ['type' => 'string']],
            'required'   => ['title'],
        ];

        $modified = $original->withSchema($schema);

        $this->assertNotSame($original, $modified);
        $this->assertSame($schema, $modified->inputSchema);
        $this->assertSame([], $original->inputSchema['properties']);
    }

    public function testToAnthropicFormat(): void
    {
        $schema = [
            'type'       => 'object',
            'properties' => ['url' => ['type' => 'string']],
            'required'   => ['url'],
        ];
        $def = new ToolDefinition('fetch_url', 'Fetches a URL', $schema);

        $format = $def->toAnthropicFormat();

        $this->assertSame('fetch_url', $format['name']);
        $this->assertSame('Fetches a URL', $format['description']);
        $this->assertSame($schema, $format['input_schema']);
        $this->assertCount(3, $format);
    }
}
