<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Analysis;

use PHPUnit\Framework\TestCase;
use WpAiAgent\Analysis\AnalysisResult;

final class AnalysisResultTest extends TestCase
{
    public function testToArrayReturnsAllFields(): void
    {
        $result = new AnalysisResult(
            success: true,
            sections: [['name' => 'hero']],
            fields: [['type' => 'text']],
            sectionCount: 1,
            fieldCount: 3,
            sharedCount: 0,
            isAcfPro: true,
            acfVersion: '6.3.0',
            downgradedFields: [],
            upgradeNotice: null,
        );

        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertSame(1, $array['section_count']);
        $this->assertSame(3, $array['field_count']);
        $this->assertSame(0, $array['shared_count']);
        $this->assertTrue($array['is_acf_pro']);
        $this->assertSame('6.3.0', $array['acf_version']);
        $this->assertEmpty($array['downgraded_fields']);
        $this->assertNull($array['upgrade_notice']);
        $this->assertNull($array['error']);
    }

    public function testErrorFactoryCreatesFailedResult(): void
    {
        $result = AnalysisResult::error('Something went wrong', false, '5.12.0');

        $this->assertFalse($result->success);
        $this->assertSame('Something went wrong', $result->error);
        $this->assertFalse($result->isAcfPro);
        $this->assertSame('5.12.0', $result->acfVersion);
        $this->assertEmpty($result->sections);
        $this->assertSame(0, $result->sectionCount);
    }

    public function testToArrayIncludesDowngradedFields(): void
    {
        $downgraded = [
            ['original_type' => 'repeater', 'fallback_type' => 'group', 'field_key' => 'field_items'],
        ];

        $result = new AnalysisResult(
            success: true,
            sections: [],
            fields: [],
            sectionCount: 0,
            fieldCount: 0,
            sharedCount: 0,
            isAcfPro: false,
            acfVersion: '5.12.0',
            downgradedFields: $downgraded,
            upgradeNotice: 'Upgrade to ACF PRO',
        );

        $array = $result->toArray();

        $this->assertCount(1, $array['downgraded_fields']);
        $this->assertSame('repeater', $array['downgraded_fields'][0]['original_type']);
        $this->assertSame('Upgrade to ACF PRO', $array['upgrade_notice']);
    }
}
