<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Tools;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WpAiAgent\Tools\Implementations\GetAcfSchemaTool;
use WpAiAgent\Tools\ToolDefinition;

final class GetAcfSchemaToolTest extends TestCase
{
    private GetAcfSchemaTool $tool;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->tool = new GetAcfSchemaTool();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testGetName(): void
    {
        $this->assertSame('get_acf_schema', $this->tool->getName());
    }

    public function testGetDefinitionReturnsToolDefinition(): void
    {
        $def = $this->tool->getDefinition();

        $this->assertInstanceOf(ToolDefinition::class, $def);
        $this->assertSame('get_acf_schema', $def->name);
        $this->assertContains('group_key', $def->inputSchema['required']);
    }

    public function testExecuteReturnsErrorWhenAcfNotActive(): void
    {
        Functions\when('function_exists')->justReturn(false);

        $result = $this->tool->execute(['group_key' => 'group_abc']);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('ACF Pro is not active', $result->message);
    }

    public function testExecuteSuccess(): void
    {
        Functions\expect('sanitize_text_field')->once()->andReturnArg(0);
        Functions\expect('acf_get_fields')->once()->andReturn([
            [
                'key' => 'field_1',
                'name' => 'hero_title',
                'type' => 'text',
                'label' => 'Hero Title',
                'instructions' => 'Enter the hero title',
                'wrapper' => ['width' => '100'],
            ],
            [
                'key' => 'field_2',
                'name' => 'hero_image',
                'type' => 'image',
                'label' => 'Hero Image',
                'return_format' => 'array',
            ],
        ]);
        Functions\when('function_exists')->justReturn(true);

        $result = $this->tool->execute(['group_key' => 'group_abc']);

        $this->assertTrue($result->success);
        $this->assertCount(2, $result->data['fields']);

        // Verify only key, name, type, label are included (no excess data)
        $field = $result->data['fields'][0];
        $this->assertSame(['key', 'name', 'type', 'label'], array_keys($field));
        $this->assertSame('field_1', $field['key']);
        $this->assertSame('hero_title', $field['name']);
        $this->assertSame('text', $field['type']);
        $this->assertSame('Hero Title', $field['label']);
    }

    public function testExecuteReturnsErrorWhenNoFields(): void
    {
        Functions\when('sanitize_text_field')->returnArg();
        Functions\expect('acf_get_fields')->once()->andReturn([]);
        Functions\when('function_exists')->justReturn(true);

        $result = $this->tool->execute(['group_key' => 'group_empty']);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('No fields found', $result->message);
    }
}
