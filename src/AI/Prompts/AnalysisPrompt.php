<?php

declare(strict_types=1);

namespace WpAiAgent\AI\Prompts;

final class AnalysisPrompt
{
    /**
     * @param string[] $availableFieldTypes
     */
    public static function build(
        string $html,
        string $templateExample,
        array  $availableFieldTypes,
    ): string {
        $fieldTypeList = implode(', ', $availableFieldTypes);

        $hasGallery         = in_array('gallery', $availableFieldTypes, true);
        $hasRepeater        = in_array('repeater', $availableFieldTypes, true);
        $hasFlexibleContent = in_array('flexible_content', $availableFieldTypes, true);

        $fieldGuide = self::buildFieldGuide($hasGallery, $hasRepeater, $hasFlexibleContent);

        $templateSection = $templateExample
            ? "\n\n## Reference PHP template (match this coding style):\n```php\n{$templateExample}\n```"
            : '';

        return <<<PROMPT
You are a WordPress expert. Analyze the following static HTML and extract a structured ACF field schema.

## Rules
1. Identify each visual section (hero, features, testimonials, CTA, etc.)
2. For each section, define ACF fields that make all text/images/links editable
3. Detect repeated patterns (e.g. feature cards) and mark them as shared/reusable
4. Use ONLY these available ACF field types: {$fieldTypeList}

## Field type selection guide
{$fieldGuide}

## Output JSON format
Return a JSON object with this structure:
{
  "sections": [
    {
      "name": "hero",
      "label": "Hero Section",
      "shared": false,
      "fields": [
        { "key": "field_hero_headline", "name": "hero_headline", "label": "Headline", "type": "text" },
        { "key": "field_hero_lead", "name": "hero_lead", "label": "Lead text", "type": "textarea" }
      ]
    }
  ]
}

Important:
- Field keys must use the format: field_{section}_{name}
- Field names must be snake_case
- Group related sub-items (e.g. CTA button = text + url) using the 'group' type with sub_fields
- Mark sections that appear multiple times as "shared": true
- Return ONLY the JSON, no markdown code fences or explanation
{$templateSection}

## HTML to analyze:
{$html}
PROMPT;
    }

    private static function buildFieldGuide(bool $hasGallery, bool $hasRepeater, bool $hasFlexibleContent): string
    {
        $lines = [
            '- text/textarea: headings, short/long text, descriptions',
            '- wysiwyg: rich content with formatting',
            '- image: single image with alt text',
        ];

        if ($hasGallery) {
            $lines[] = '- gallery: multiple images';
        }

        $lines[] = '- group: related fields (e.g. CTA button = text + url)';

        if ($hasRepeater) {
            $lines[] = '- repeater: list of identical items (cards, features, team members)';
        } else {
            $lines[] = '- group: use for repeatable items (repeater not available in ACF Free)';
        }

        if ($hasFlexibleContent) {
            $lines[] = '- flexible_content: sections with different layouts';
        } else {
            $lines[] = '- multiple groups: use instead of flexible_content (not available in ACF Free)';
        }

        $lines = array_merge($lines, [
            '- true_false: boolean toggles',
            '- select/radio: multiple choice',
            '- link: URL + label grouped',
            '- date_picker: dates',
            '- color_picker: color values',
        ]);

        return implode("\n", $lines);
    }
}
