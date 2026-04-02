<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Acf;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use WpAiAgent\Acf\AcfVersionDetector;
use WpAiAgent\Acf\ProFieldUpgrader;

final class AcfVersionDetectorTest extends TestCase
{
    private AcfVersionDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->detector = new AcfVersionDetector();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testFreeFieldsConstantIsNotEmpty(): void
    {
        $this->assertNotEmpty(AcfVersionDetector::FREE_FIELDS);
        $this->assertContains('text', AcfVersionDetector::FREE_FIELDS);
        $this->assertContains('image', AcfVersionDetector::FREE_FIELDS);
        $this->assertContains('group', AcfVersionDetector::FREE_FIELDS);
    }

    public function testProOnlyFieldsConstantContainsExpectedTypes(): void
    {
        $this->assertContains('repeater', AcfVersionDetector::PRO_ONLY_FIELDS);
        $this->assertContains('flexible_content', AcfVersionDetector::PRO_ONLY_FIELDS);
        $this->assertContains('gallery', AcfVersionDetector::PRO_ONLY_FIELDS);
        $this->assertContains('clone', AcfVersionDetector::PRO_ONLY_FIELDS);
        $this->assertContains('relationship', AcfVersionDetector::PRO_ONLY_FIELDS);
    }

    public function testFreeAndProFieldsDoNotOverlap(): void
    {
        $overlap = array_intersect(AcfVersionDetector::FREE_FIELDS, AcfVersionDetector::PRO_ONLY_FIELDS);
        $this->assertEmpty($overlap, 'Free and Pro field lists must not overlap');
    }

    public function testGetVersionReturnsString(): void
    {
        $version = $this->detector->getVersion();
        $this->assertIsString($version);
    }

    public function testSupportsFieldReturnsTrueForFreeFields(): void
    {
        foreach (['text', 'image', 'select', 'group', 'wysiwyg'] as $type) {
            $this->assertTrue(
                $this->detector->supportsField($type),
                "Free field type '{$type}' should always be supported"
            );
        }
    }

    public function testSupportsFieldReturnsFalseForUnknownType(): void
    {
        $this->assertFalse($this->detector->supportsField('nonexistent_type'));
    }

    public function testGetAvailableFieldTypesContainsFreeFields(): void
    {
        $types = $this->detector->getAvailableFieldTypes();

        foreach (AcfVersionDetector::FREE_FIELDS as $free) {
            $this->assertContains($free, $types);
        }
    }

    public function testFilterProOnlyFieldsDowngradesProTypes(): void
    {
        // Since we can't mock isPro, we rely on the fact that acf_pro class
        // does not exist in our test environment, so isPro() returns false.
        $upgrader = new ProFieldUpgrader();
        $fields = [
            ['type' => 'text', 'key' => 'field_title', 'name' => 'title'],
            ['type' => 'repeater', 'key' => 'field_items', 'name' => 'items', 'sub_fields' => []],
        ];

        $result = $this->detector->filterProOnlyFields($fields, $upgrader);

        $this->assertSame('text', $result[0]['type']);
        // repeater is in PRO_ONLY_FIELDS so it should be downgraded
        $this->assertSame('group', $result[1]['type']);
    }

    public function testGetUpgradeNoticeReturnsNullWhenNoProFields(): void
    {
        $this->assertNull($this->detector->getUpgradeNotice([]));
    }

    public function testGetUpgradeNoticeReturnsStringWhenFieldsSuggested(): void
    {
        // In test env, isPro() returns false (no ACF_PRO constant, no acf_pro class)
        $notice = $this->detector->getUpgradeNotice(['repeater', 'gallery']);

        $this->assertNotNull($notice);
        $this->assertStringContainsString('repeater', $notice);
        $this->assertStringContainsString('gallery', $notice);
        $this->assertStringContainsString('ACF PRO', $notice);
    }

    public function testIsActiveReturnsBoolInTestEnvironment(): void
    {
        // In test env, ACF is not loaded, so should return false
        $this->assertIsBool($this->detector->isActive());
    }

    public function testIsProReturnsBoolInTestEnvironment(): void
    {
        $this->assertIsBool($this->detector->isPro());
    }
}
