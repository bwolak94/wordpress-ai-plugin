<?php

declare(strict_types=1);

namespace WpAiAgent\Analysis;

final class AnalysisResult
{
    /**
     * @param array<array<string, mixed>> $sections
     * @param array<array<string, mixed>> $fields
     * @param array<array{original_type: string, fallback_type: string, field_key: string}> $downgradedFields
     */
    public function __construct(
        public readonly bool    $success,
        public readonly array   $sections,
        public readonly array   $fields,
        public readonly int     $sectionCount,
        public readonly int     $fieldCount,
        public readonly int     $sharedCount,
        public readonly bool    $isAcfPro,
        public readonly string  $acfVersion,
        public readonly array   $downgradedFields,
        public readonly ?string $upgradeNotice,
        public readonly ?string $error = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'success'           => $this->success,
            'sections'          => $this->sections,
            'fields'            => $this->fields,
            'section_count'     => $this->sectionCount,
            'field_count'       => $this->fieldCount,
            'shared_count'      => $this->sharedCount,
            'is_acf_pro'        => $this->isAcfPro,
            'acf_version'       => $this->acfVersion,
            'downgraded_fields' => $this->downgradedFields,
            'upgrade_notice'    => $this->upgradeNotice,
            'error'             => $this->error,
        ];
    }

    public static function error(string $message, bool $isAcfPro, string $acfVersion): self
    {
        return new self(
            success: false,
            sections: [],
            fields: [],
            sectionCount: 0,
            fieldCount: 0,
            sharedCount: 0,
            isAcfPro: $isAcfPro,
            acfVersion: $acfVersion,
            downgradedFields: [],
            upgradeNotice: null,
            error: $message,
        );
    }
}
