<?php

declare(strict_types=1);

namespace WpAiAgent\Acf;

final class AcfFieldFactory
{
    /** @var array<array{original_type: string, fallback_type: string, field_key: string}> */
    private array $downgradedFields = [];

    public function __construct(
        private readonly AcfVersionDetector $detector,
        private readonly ProFieldUpgrader $upgrader,
    ) {}

    /**
     * Create a field definition, downgrading PRO types if needed.
     *
     * @param array<string, mixed> $fieldDef
     * @return array<string, mixed>
     */
    public function create(array $fieldDef): array
    {
        $type = $fieldDef['type'] ?? 'text';

        if (!$this->detector->supportsField($type)) {
            $fieldDef = $this->upgrader->upgrade($fieldDef);

            $this->downgradedFields[] = [
                'original_type' => $type,
                'fallback_type' => $fieldDef['type'],
                'field_key'     => $fieldDef['key'] ?? $fieldDef['name'] ?? 'unknown',
            ];
        }

        if (!empty($fieldDef['sub_fields']) && is_array($fieldDef['sub_fields'])) {
            $fieldDef['sub_fields'] = array_map(
                fn(array $sub): array => $this->create($sub),
                $fieldDef['sub_fields'],
            );
        }

        return $fieldDef;
    }

    /**
     * Process multiple field definitions.
     *
     * @param array<array<string, mixed>> $fields
     * @return array<array<string, mixed>>
     */
    public function createAll(array $fields): array
    {
        return array_map(fn(array $f): array => $this->create($f), $fields);
    }

    /** @return array<array{original_type: string, fallback_type: string, field_key: string}> */
    public function getDowngradedFields(): array
    {
        return array_merge($this->downgradedFields, $this->upgrader->getDowngradedFields());
    }

    public function reset(): void
    {
        $this->downgradedFields = [];
        $this->upgrader->resetDowngradedFields();
    }
}
