<?php

declare(strict_types=1);

namespace WpAiAgent\Tools\Implementations;

use WpAiAgent\Tools\Contracts\ToolInterface;
use WpAiAgent\Tools\ToolDefinition;
use WpAiAgent\Tools\ToolResult;

final class SetAcfFieldTool implements ToolInterface
{
    public function getName(): string
    {
        return 'set_acf_field';
    }

    public function getDefinition(): ToolDefinition
    {
        return ToolDefinition::make(
            name: 'set_acf_field',
            description: 'Sets an ACF (Advanced Custom Fields) field value on a WordPress post or page. '
                . 'Use after create_page to populate custom field data. '
                . 'Requires ACF Pro to be active.'
        )->withSchema([
            'type' => 'object',
            'properties' => [
                'post_id'   => ['type' => 'integer', 'description' => 'Post ID returned by create_page'],
                'field_key' => ['type' => 'string', 'description' => 'ACF field key (starts with "field_") or field name'],
                'value'     => ['description' => 'Field value — string, number, array depending on field type'],
            ],
            'required' => ['post_id', 'field_key', 'value'],
        ]);
    }

    public function execute(array $params): ToolResult
    {
        if (!function_exists('update_field')) {
            return ToolResult::error('ACF Pro is not active');
        }

        $postId   = (int) $params['post_id'];
        $fieldKey = sanitize_text_field($params['field_key']);
        $value    = $params['value'];

        $result = update_field($fieldKey, $value, $postId);

        if ($result === false) {
            return ToolResult::error("Failed to set ACF field '{$fieldKey}' on post {$postId}");
        }

        return ToolResult::success(
            message: "ACF field '{$fieldKey}' set on post {$postId}",
            data: ['post_id' => $postId, 'field_key' => $fieldKey],
        );
    }
}
