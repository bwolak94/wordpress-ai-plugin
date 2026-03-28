<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WpAiAgent\DTO\Brief;

final class BriefTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    private function stubSanitizers(): void
    {
        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testFromArrayReturnsInstance(): void
    {
        $this->stubSanitizers();

        $brief = Brief::fromArray([
            'documentation' => 'Some docs',
            'goals' => 'Build a page',
        ]);

        $this->assertInstanceOf(Brief::class, $brief);
        $this->assertSame('Some docs', $brief->documentation);
        $this->assertSame('Build a page', $brief->goals);
        $this->assertSame('', $brief->targetUrl);
        $this->assertNull($brief->parentPageId);
        $this->assertNull($brief->acfGroupKey);
        $this->assertSame([], $brief->context);
    }

    public function testFromArrayWithAllFields(): void
    {
        $this->stubSanitizers();

        $brief = Brief::fromArray([
            'documentation' => 'docs',
            'goals' => 'goals',
            'target_url' => 'https://example.com',
            'parent_id' => 42,
            'acf_group_key' => 'group_abc',
            'context' => ['key' => 'value'],
        ]);

        $this->assertSame('https://example.com', $brief->targetUrl);
        $this->assertSame(42, $brief->parentPageId);
        $this->assertSame('group_abc', $brief->acfGroupKey);
        $this->assertSame(['key' => 'value'], $brief->context);
    }

    public function testFromArrayThrowsOnMissingDocumentation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('documentation is required');

        Brief::fromArray(['goals' => 'something']);
    }

    public function testFromArrayThrowsOnMissingGoals(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('goals is required');

        Brief::fromArray(['documentation' => 'something']);
    }

    public function testFromArrayThrowsOnEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Brief::fromArray([]);
    }

    public function testSanitizationFunctionsAreCalled(): void
    {
        Functions\expect('sanitize_textarea_field')
            ->twice()
            ->andReturnUsing(fn(string $v): string => trim($v));
        Functions\expect('sanitize_text_field')
            ->once()
            ->andReturn('cleaned-url');

        $brief = Brief::fromArray([
            'documentation' => ' docs ',
            'goals' => ' goals ',
            'target_url' => '<script>bad</script>',
        ]);

        $this->assertSame('docs', $brief->documentation);
        $this->assertSame('goals', $brief->goals);
        $this->assertSame('cleaned-url', $brief->targetUrl);
    }
}
