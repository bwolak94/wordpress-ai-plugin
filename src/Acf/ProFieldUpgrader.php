<?php

declare(strict_types=1);

namespace WpAiAgent\Acf;

final class ProFieldUpgrader
{
    /** @var array<array{original_type: string, fallback_type: string, field_key: string}> */
    private array $downgradedFields = [];

    /**
     * Downgrade a single PRO-only field to a FREE equivalent.
     *
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    public function upgrade(array $field): array
    {
        $type = $field['type'] ?? '';
        $key  = $field['key'] ?? $field['name'] ?? 'unknown';

        return match ($type) {
            'repeater'         => $this->downgradeRepeater($field, $key),
            'flexible_content' => $this->downgradeFlexibleContent($field, $key),
            'gallery'          => $this->downgradeGallery($field, $key),
            'clone'            => $this->downgradeClone($field, $key),
            'relationship'     => $this->downgradeRelationship($field, $key),
            default            => $field,
        };
    }

    /**
     * Recursively upgrade all fields including sub_fields.
     *
     * @param array<array<string, mixed>> $fields
     * @return array<array<string, mixed>>
     */
    public function upgradeAll(array $fields): array
    {
        return array_map(function (array $field): array {
            if (in_array($field['type'] ?? '', AcfVersionDetector::PRO_ONLY_FIELDS, true)) {
                $field = $this->upgrade($field);
            }

            if (!empty($field['sub_fields']) && is_array($field['sub_fields'])) {
                $field['sub_fields'] = $this->upgradeAll($field['sub_fields']);
            }

            return $field;
        }, $fields);
    }

    /** @return array<array{original_type: string, fallback_type: string, field_key: string}> */
    public function getDowngradedFields(): array
    {
        return $this->downgradedFields;
    }

    public function resetDowngradedFields(): void
    {
        $this->downgradedFields = [];
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private function downgradeRepeater(array $field, string $key): array
    {
        $this->trackDowngrade('repeater', 'group', $key);

        $subFields = $field['sub_fields'] ?? [];

        return array_merge($field, [
            'type'         => 'group',
            'instructions' => $this->notice(
                'Repeater requires ACF PRO. Using Group field as fallback. '
                . 'Multiple items must be managed manually or upgrade to ACF PRO.',
                $field['instructions'] ?? '',
            ),
            'sub_fields'   => $subFields,
        ]);
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private function downgradeFlexibleContent(array $field, string $key): array
    {
        $this->trackDowngrade('flexible_content', 'group', $key);

        $layouts   = $field['layouts'] ?? [];
        $subFields = [];

        foreach ($layouts as $layout) {
            $subFields[] = [
                'key'          => ($layout['key'] ?? '') . '_group',
                'label'        => $layout['label'] ?? $layout['name'] ?? 'Section',
                'name'         => $layout['name'] ?? 'section',
                'type'         => 'group',
                'sub_fields'   => $layout['sub_fields'] ?? [],
                'instructions' => 'Layout: ' . ($layout['label'] ?? $layout['name'] ?? ''),
            ];
        }

        return array_merge($field, [
            'type'         => 'group',
            'instructions' => $this->notice(
                'Flexible Content requires ACF PRO. Each section is a separate group. '
                . 'Consider ACF PRO for full flexibility.',
                $field['instructions'] ?? '',
            ),
            'sub_fields'   => $subFields,
            'layouts'      => null,
        ]);
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private function downgradeGallery(array $field, string $key): array
    {
        $this->trackDowngrade('gallery', 'image', $key);

        return array_merge($field, [
            'type'         => 'image',
            'instructions' => $this->notice(
                'Gallery requires ACF PRO. Using single Image field. '
                . 'Upgrade for multi-image gallery support.',
                $field['instructions'] ?? '',
            ),
        ]);
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private function downgradeClone(array $field, string $key): array
    {
        $this->trackDowngrade('clone', 'text', $key);

        return array_merge($field, [
            'type'         => 'text',
            'instructions' => $this->notice(
                'Clone field requires ACF PRO. Using text field as placeholder. '
                . 'Manually replicate the cloned field group or upgrade to ACF PRO.',
                $field['instructions'] ?? '',
            ),
        ]);
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private function downgradeRelationship(array $field, string $key): array
    {
        $this->trackDowngrade('relationship', 'post_object', $key);

        return array_merge($field, [
            'type'         => 'post_object',
            'instructions' => $this->notice(
                'Relationship (multiple posts) requires ACF PRO. '
                . 'Using Post Object for single post selection.',
                $field['instructions'] ?? '',
            ),
        ]);
    }

    private function trackDowngrade(string $originalType, string $fallbackType, string $fieldKey): void
    {
        $this->downgradedFields[] = [
            'original_type' => $originalType,
            'fallback_type' => $fallbackType,
            'field_key'     => $fieldKey,
        ];
    }

    private function notice(string $warning, string $existing): string
    {
        $prefix = "⚠️ {$warning}";
        return $existing ? "{$prefix}\n\n{$existing}" : $prefix;
    }
}
