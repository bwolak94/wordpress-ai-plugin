<?php

declare(strict_types=1);

namespace WpAiAgent\DTO;

final class Brief
{
    private function __construct(
        public readonly string $documentation,
        public readonly string $goals,
        public readonly string $targetUrl,
        public readonly ?int $parentPageId = null,
        public readonly ?string $acfGroupKey = null,
        public readonly array $context = [],
    ) {}

    public static function fromArray(array $data): self
    {
        if (empty($data['documentation'])) {
            throw new \InvalidArgumentException('documentation is required');
        }

        if (empty($data['goals'])) {
            throw new \InvalidArgumentException('goals is required');
        }

        return new self(
            documentation: sanitize_textarea_field($data['documentation']),
            goals: sanitize_textarea_field($data['goals']),
            targetUrl: sanitize_text_field($data['target_url'] ?? ''),
            parentPageId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            acfGroupKey: $data['acf_group_key'] ?? null,
            context: (array) ($data['context'] ?? []),
        );
    }
}
