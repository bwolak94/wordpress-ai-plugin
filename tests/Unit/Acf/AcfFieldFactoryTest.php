<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Acf;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use WpAiAgent\Acf\AcfFieldFactory;
use WpAiAgent\Acf\AcfVersionDetector;
use WpAiAgent\Acf\ProFieldUpgrader;

final class AcfFieldFactoryTest extends TestCase
{
    private AcfFieldFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // In test env: ACF is not loaded, so isPro() = false, isActive() = false
        $detector = new AcfVersionDetector();
        $upgrader = new ProFieldUpgrader();
        $this->factory = new AcfFieldFactory($detector, $upgrader);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testCreatePassesThroughFreeFields(): void
    {
        $field = ['type' => 'text', 'key' => 'field_title', 'name' => 'title'];

        $result = $this->factory->create($field);

        $this->assertSame('text', $result['type']);
        $this->assertEmpty($this->factory->getDowngradedFields());
    }

    public function testCreateDowngradesProFields(): void
    {
        $field = ['type' => 'repeater', 'key' => 'field_items', 'name' => 'items', 'sub_fields' => []];

        $result = $this->factory->create($field);

        $this->assertSame('group', $result['type']);

        $downgraded = $this->factory->getDowngradedFields();
        $this->assertNotEmpty($downgraded);
        $this->assertSame('repeater', $downgraded[0]['original_type']);
    }

    public function testCreateAllProcessesMultipleFields(): void
    {
        $fields = [
            ['type' => 'text', 'key' => 'field_a', 'name' => 'a'],
            ['type' => 'gallery', 'key' => 'field_b', 'name' => 'b'],
            ['type' => 'image', 'key' => 'field_c', 'name' => 'c'],
        ];

        $result = $this->factory->createAll($fields);

        $this->assertSame('text', $result[0]['type']);
        $this->assertSame('image', $result[1]['type']); // gallery → image
        $this->assertSame('image', $result[2]['type']);
    }

    public function testCreateRecursivelyProcessesSubFields(): void
    {
        $field = [
            'type'       => 'group',
            'key'        => 'field_group',
            'name'       => 'my_group',
            'sub_fields' => [
                ['type' => 'gallery', 'key' => 'field_inner_gallery', 'name' => 'inner_gallery'],
            ],
        ];

        $result = $this->factory->create($field);

        $this->assertSame('group', $result['type']);
        $this->assertSame('image', $result['sub_fields'][0]['type']);
    }

    public function testResetClearsTracking(): void
    {
        $this->factory->create(['type' => 'repeater', 'key' => 'field_x', 'name' => 'x', 'sub_fields' => []]);
        $this->assertNotEmpty($this->factory->getDowngradedFields());

        $this->factory->reset();
        $this->assertEmpty($this->factory->getDowngradedFields());
    }
}
