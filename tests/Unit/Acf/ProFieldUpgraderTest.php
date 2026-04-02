<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Acf;

use PHPUnit\Framework\TestCase;
use WpAiAgent\Acf\ProFieldUpgrader;

final class ProFieldUpgraderTest extends TestCase
{
    private ProFieldUpgrader $upgrader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->upgrader = new ProFieldUpgrader();
    }

    public function testUpgradeRepeaterToGroup(): void
    {
        $field = [
            'type'       => 'repeater',
            'key'        => 'field_items',
            'name'       => 'items',
            'sub_fields' => [
                ['type' => 'text', 'key' => 'field_item_title', 'name' => 'item_title'],
            ],
        ];

        $result = $this->upgrader->upgrade($field);

        $this->assertSame('group', $result['type']);
        $this->assertCount(1, $result['sub_fields']);
        $this->assertStringContainsString('Repeater requires ACF PRO', $result['instructions']);
    }

    public function testUpgradeGalleryToImage(): void
    {
        $field = [
            'type' => 'gallery',
            'key'  => 'field_photos',
            'name' => 'photos',
        ];

        $result = $this->upgrader->upgrade($field);

        $this->assertSame('image', $result['type']);
        $this->assertStringContainsString('Gallery requires ACF PRO', $result['instructions']);
    }

    public function testUpgradeRelationshipToPostObject(): void
    {
        $field = [
            'type' => 'relationship',
            'key'  => 'field_related',
            'name' => 'related_posts',
        ];

        $result = $this->upgrader->upgrade($field);

        $this->assertSame('post_object', $result['type']);
        $this->assertStringContainsString('Relationship', $result['instructions']);
    }

    public function testUpgradeCloneToText(): void
    {
        $field = [
            'type' => 'clone',
            'key'  => 'field_cloned',
            'name' => 'cloned_group',
        ];

        $result = $this->upgrader->upgrade($field);

        $this->assertSame('text', $result['type']);
        $this->assertStringContainsString('Clone field requires ACF PRO', $result['instructions']);
    }

    public function testUpgradeFlexibleContentToGroup(): void
    {
        $field = [
            'type'    => 'flexible_content',
            'key'     => 'field_sections',
            'name'    => 'sections',
            'layouts' => [
                [
                    'key'        => 'layout_hero',
                    'name'       => 'hero',
                    'label'      => 'Hero',
                    'sub_fields' => [
                        ['type' => 'text', 'key' => 'field_hero_title', 'name' => 'hero_title'],
                    ],
                ],
                [
                    'key'        => 'layout_cta',
                    'name'       => 'cta',
                    'label'      => 'CTA',
                    'sub_fields' => [
                        ['type' => 'url', 'key' => 'field_cta_url', 'name' => 'cta_url'],
                    ],
                ],
            ],
        ];

        $result = $this->upgrader->upgrade($field);

        $this->assertSame('group', $result['type']);
        $this->assertCount(2, $result['sub_fields']);
        $this->assertSame('group', $result['sub_fields'][0]['type']);
        $this->assertSame('hero', $result['sub_fields'][0]['name']);
        $this->assertStringContainsString('Flexible Content requires ACF PRO', $result['instructions']);
    }

    public function testUpgradePassesThroughUnknownTypes(): void
    {
        $field = ['type' => 'text', 'key' => 'field_test', 'name' => 'test'];

        $result = $this->upgrader->upgrade($field);

        $this->assertSame('text', $result['type']);
    }

    public function testGetDowngradedFieldsTracksChanges(): void
    {
        $this->upgrader->upgrade(['type' => 'repeater', 'key' => 'field_a', 'name' => 'a', 'sub_fields' => []]);
        $this->upgrader->upgrade(['type' => 'gallery', 'key' => 'field_b', 'name' => 'b']);

        $downgraded = $this->upgrader->getDowngradedFields();

        $this->assertCount(2, $downgraded);
        $this->assertSame('repeater', $downgraded[0]['original_type']);
        $this->assertSame('group', $downgraded[0]['fallback_type']);
        $this->assertSame('gallery', $downgraded[1]['original_type']);
        $this->assertSame('image', $downgraded[1]['fallback_type']);
    }

    public function testResetDowngradedFieldsClearsTracking(): void
    {
        $this->upgrader->upgrade(['type' => 'repeater', 'key' => 'field_x', 'name' => 'x', 'sub_fields' => []]);
        $this->assertCount(1, $this->upgrader->getDowngradedFields());

        $this->upgrader->resetDowngradedFields();
        $this->assertCount(0, $this->upgrader->getDowngradedFields());
    }

    public function testUpgradeAllRecursivelyProcessesFields(): void
    {
        $fields = [
            ['type' => 'text', 'key' => 'field_title', 'name' => 'title'],
            [
                'type'       => 'repeater',
                'key'        => 'field_items',
                'name'       => 'items',
                'sub_fields' => [
                    ['type' => 'gallery', 'key' => 'field_gallery', 'name' => 'gallery'],
                ],
            ],
        ];

        $result = $this->upgrader->upgradeAll($fields);

        $this->assertSame('text', $result[0]['type']);
        $this->assertSame('group', $result[1]['type']);
        $this->assertSame('image', $result[1]['sub_fields'][0]['type']);
    }

    public function testInstructionsPreservesExistingContent(): void
    {
        $field = [
            'type'         => 'gallery',
            'key'          => 'field_pics',
            'name'         => 'pics',
            'instructions' => 'Upload product images.',
        ];

        $result = $this->upgrader->upgrade($field);

        $this->assertStringContainsString('Gallery requires ACF PRO', $result['instructions']);
        $this->assertStringContainsString('Upload product images.', $result['instructions']);
    }
}
