<?php

declare(strict_types=1);

namespace WpAiAgent\Acf;

final class AcfGroupBuilder
{
    public function __construct(
        private readonly AcfFieldFactory $fieldFactory,
    ) {}

    /**
     * Build a complete ACF field group from analysed sections.
     *
     * @param string $title
     * @param string $prefix
     * @param array<array<string, mixed>> $fields  Raw field definitions from AI analysis
     * @return array<string, mixed>
     */
    public function build(string $title, string $prefix, array $fields): array
    {
        $groupKey = 'group_' . $prefix . '_' . substr(md5($title . microtime()), 0, 6);

        $processedFields = $this->fieldFactory->createAll($fields);

        return [
            'key'      => $groupKey,
            'title'    => $title,
            'fields'   => $processedFields,
            'location' => [
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'page',
                    ],
                ],
            ],
        ];
    }

    /** @return array<array{original_type: string, fallback_type: string, field_key: string}> */
    public function getDowngradedFields(): array
    {
        return $this->fieldFactory->getDowngradedFields();
    }
}
