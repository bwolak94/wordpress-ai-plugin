<?php

declare(strict_types=1);

namespace WpAiAgent\Analysis;

use WpAiAgent\Acf\AcfFieldFactory;
use WpAiAgent\Acf\AcfVersionDetector;
use WpAiAgent\AI\Contracts\AIProviderInterface;
use WpAiAgent\AI\Prompts\AnalysisPrompt;

final class HtmlAnalyzerService
{
    public function __construct(
        private readonly AIProviderInterface $ai,
        private readonly AcfVersionDetector  $detector,
        private readonly AcfFieldFactory     $fieldFactory,
    ) {}

    public function analyze(string $html, string $templateExample = ''): AnalysisResult
    {
        $isAcfPro   = $this->detector->isPro();
        $acfVersion = $this->detector->getVersion();

        if (!$this->detector->isActive()) {
            return AnalysisResult::error('ACF plugin is not active.', $isAcfPro, $acfVersion);
        }

        $availableTypes = $this->detector->getAvailableFieldTypes();

        $prompt = AnalysisPrompt::build($html, $templateExample, $availableTypes);

        $response = $this->ai->sendMessage(
            [['role' => 'user', 'content' => $prompt]],
            [],
        );

        $content = $response['content'] ?? [];
        $text    = $this->extractText($content);

        $parsed = json_decode($text, true);

        if (!is_array($parsed) || empty($parsed['sections'])) {
            return AnalysisResult::error(
                'Failed to parse AI response as valid section schema.',
                $isAcfPro,
                $acfVersion,
            );
        }

        $this->fieldFactory->reset();

        $sections   = $parsed['sections'];
        $allFields  = [];
        $sharedCount = 0;

        foreach ($sections as &$section) {
            $rawFields = $section['fields'] ?? [];
            $section['fields'] = $this->fieldFactory->createAll($rawFields);
            $allFields = array_merge($allFields, $section['fields']);

            if (!empty($section['shared'])) {
                $sharedCount++;
            }
        }
        unset($section);

        $downgradedFields = $this->fieldFactory->getDowngradedFields();
        $suggestedProTypes = array_map(
            fn(array $d): string => $d['original_type'],
            $downgradedFields,
        );

        return new AnalysisResult(
            success: true,
            sections: $sections,
            fields: $allFields,
            sectionCount: count($sections),
            fieldCount: $this->countFieldsRecursive($allFields),
            sharedCount: $sharedCount,
            isAcfPro: $isAcfPro,
            acfVersion: $acfVersion,
            downgradedFields: $downgradedFields,
            upgradeNotice: $this->detector->getUpgradeNotice($suggestedProTypes),
        );
    }

    /**
     * @param array<mixed> $content
     */
    private function extractText(array $content): string
    {
        foreach ($content as $block) {
            if (is_array($block) && ($block['type'] ?? '') === 'text') {
                return trim($block['text'] ?? '');
            }

            if (is_string($block)) {
                return trim($block);
            }
        }

        return '';
    }

    /**
     * @param array<array<string, mixed>> $fields
     */
    private function countFieldsRecursive(array $fields): int
    {
        $count = count($fields);

        foreach ($fields as $field) {
            if (!empty($field['sub_fields']) && is_array($field['sub_fields'])) {
                $count += $this->countFieldsRecursive($field['sub_fields']);
            }
        }

        return $count;
    }
}
