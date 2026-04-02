<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\AI;

use PHPUnit\Framework\TestCase;
use WpAiAgent\Acf\AcfVersionDetector;
use WpAiAgent\AI\Prompts\AnalysisPrompt;

final class AnalysisPromptTest extends TestCase
{
    public function testBuildContainsHtmlInput(): void
    {
        $prompt = AnalysisPrompt::build(
            '<section class="hero"><h1>Title</h1></section>',
            '',
            AcfVersionDetector::FREE_FIELDS,
        );

        $this->assertStringContainsString('<section class="hero">', $prompt);
    }

    public function testBuildListsAvailableFieldTypes(): void
    {
        $types = ['text', 'textarea', 'image', 'group'];

        $prompt = AnalysisPrompt::build('<div>test</div>', '', $types);

        $this->assertStringContainsString('text, textarea, image, group', $prompt);
    }

    public function testBuildIncludesRepeaterGuideWhenAvailable(): void
    {
        $types = array_merge(AcfVersionDetector::FREE_FIELDS, ['repeater']);

        $prompt = AnalysisPrompt::build('<div>test</div>', '', $types);

        $this->assertStringContainsString('repeater: list of identical items', $prompt);
    }

    public function testBuildExcludesRepeaterGuideWhenNotAvailable(): void
    {
        $prompt = AnalysisPrompt::build('<div>test</div>', '', AcfVersionDetector::FREE_FIELDS);

        $this->assertStringNotContainsString('repeater: list of identical items', $prompt);
        $this->assertStringContainsString('repeater not available in ACF Free', $prompt);
    }

    public function testBuildIncludesFlexibleContentGuideWhenAvailable(): void
    {
        $types = array_merge(AcfVersionDetector::FREE_FIELDS, ['flexible_content']);

        $prompt = AnalysisPrompt::build('<div>test</div>', '', $types);

        $this->assertStringContainsString('flexible_content: sections with different layouts', $prompt);
    }

    public function testBuildIncludesGalleryGuideWhenAvailable(): void
    {
        $types = array_merge(AcfVersionDetector::FREE_FIELDS, ['gallery']);

        $prompt = AnalysisPrompt::build('<div>test</div>', '', $types);

        $this->assertStringContainsString('gallery: multiple images', $prompt);
    }

    public function testBuildIncludesTemplateWhenProvided(): void
    {
        $template = '<?php echo get_field("hero_title"); ?>';

        $prompt = AnalysisPrompt::build('<div>test</div>', $template, ['text']);

        $this->assertStringContainsString('Reference PHP template', $prompt);
        $this->assertStringContainsString($template, $prompt);
    }

    public function testBuildExcludesTemplateWhenEmpty(): void
    {
        $prompt = AnalysisPrompt::build('<div>test</div>', '', ['text']);

        $this->assertStringNotContainsString('Reference PHP template', $prompt);
    }

    public function testBuildRequestsJsonFormat(): void
    {
        $prompt = AnalysisPrompt::build('<div>test</div>', '', ['text']);

        $this->assertStringContainsString('"sections"', $prompt);
        $this->assertStringContainsString('Return ONLY the JSON', $prompt);
    }
}
