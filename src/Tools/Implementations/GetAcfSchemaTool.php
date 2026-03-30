<?php

declare(strict_types=1);

namespace WpAiAgent\Tools\Implementations;

use WpAiAgent\Tools\Contracts\ToolInterface;
use WpAiAgent\Tools\ToolDefinition;
use WpAiAgent\Tools\ToolResult;

final class GetAcfSchemaTool implements ToolInterface
{
    public function getName(): string
    {
        return 'get_acf_schema';
    }

    public function getDefinition(): ToolDefinition
    {
        return ToolDefinition::make(
            name: 'get_acf_schema',
            description: 'Returns the list of ACF fields for a given ACF group key. '
                . 'Call this first to discover available fields before calling set_acf_field.'
        )->withSchema([
            'type' => 'object',
            'properties' => [
                'group_key' => ['type' => 'string', 'description' => 'ACF field group key, e.g. "group_abc123"'],
            ],
            'required' => ['group_key'],
        ]);
    }

    public function execute(array $params): ToolResult
    {
        if (!function_exists('acf_get_fields')) {
            return ToolResult::error('ACF Pro is not active');
        }

        $groupKey = sanitize_text_field($params['group_key']);
        $fields   = acf_get_fields($groupKey);

        if (empty($fields)) {
            return ToolResult::error("No fields found for group key '{$groupKey}'");
        }

        $schema = array_map(fn($f) => [
            'key'   => $f['key'],
            'name'  => $f['name'],
            'type'  => $f['type'],
            'label' => $f['label'],
        ], $fields);

        return ToolResult::success(
            message: "Found " . count($schema) . " fields in group '{$groupKey}'",
            data: ['fields' => $schema],
        );
    }
}
