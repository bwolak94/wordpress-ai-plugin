<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Acf;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use WpAiAgent\Acf\AcfFieldFactory;
use WpAiAgent\Acf\AcfGroupBuilder;
use WpAiAgent\Acf\AcfVersionDetector;
use WpAiAgent\Acf\ProFieldUpgrader;

final class AcfGroupBuilderTest extends TestCase
{
    private AcfGroupBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $detector     = new AcfVersionDetector();
        $upgrader     = new ProFieldUpgrader();
        $fieldFactory = new AcfFieldFactory($detector, $upgrader);
        $this->builder = new AcfGroupBuilder($fieldFactory);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testBuildReturnsValidGroupStructure(): void
    {
        $fields = [
            ['type' => 'text', 'key' => 'field_title', 'name' => 'title', 'label' => 'Title'],
            ['type' => 'textarea', 'key' => 'field_desc', 'name' => 'desc', 'label' => 'Description'],
        ];

        $group = $this->builder->build('Hero Section', 'hero', $fields);

        $this->assertStringStartsWith('group_hero_', $group['key']);
        $this->assertSame('Hero Section', $group['title']);
        $this->assertCount(2, $group['fields']);
        $this->assertArrayHasKey('location', $group);
    }

    public function testBuildDowngradesProFields(): void
    {
        $fields = [
            ['type' => 'text', 'key' => 'field_title', 'name' => 'title'],
            ['type' => 'repeater', 'key' => 'field_items', 'name' => 'items', 'sub_fields' => []],
        ];

        $group = $this->builder->build('Test', 'test', $fields);

        $this->assertSame('group', $group['fields'][1]['type']);

        $downgraded = $this->builder->getDowngradedFields();
        $this->assertNotEmpty($downgraded);
    }

    public function testBuildGeneratesUniqueGroupKeys(): void
    {
        $fields = [['type' => 'text', 'key' => 'field_a', 'name' => 'a']];

        $group1 = $this->builder->build('Page A', 'page', $fields);
        usleep(1);
        $group2 = $this->builder->build('Page B', 'page', $fields);

        $this->assertNotSame($group1['key'], $group2['key']);
    }
}
