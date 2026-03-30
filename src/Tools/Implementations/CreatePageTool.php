<?php

declare(strict_types=1);

namespace WpAiAgent\Tools\Implementations;

use WpAiAgent\Tools\Contracts\ToolInterface;
use WpAiAgent\Tools\ToolDefinition;
use WpAiAgent\Tools\ToolResult;

final class CreatePageTool implements ToolInterface
{
    public function getName(): string
    {
        return 'create_page';
    }

    public function getDefinition(): ToolDefinition
    {
        return ToolDefinition::make(
            name: 'create_page',
            description: 'Creates a WordPress page with given title and content. '
                . 'Use for main landing pages and subpages. '
                . 'Returns the page ID for use in subsequent tool calls.'
        )->withSchema([
            'type' => 'object',
            'properties' => [
                'title'     => ['type' => 'string', 'description' => 'Page title — visible in browser tab and H1'],
                'content'   => ['type' => 'string', 'description' => 'Main content in HTML. Can be empty if ACF handles content.'],
                'slug'      => ['type' => 'string', 'description' => 'URL slug, e.g. "oferta-seo". Lowercase, hyphens.'],
                'parent_id' => ['type' => 'integer', 'description' => 'Parent page ID for subpages. 0 for top-level.'],
                'status'    => ['type' => 'string', 'enum' => ['draft', 'publish'], 'default' => 'draft'],
            ],
            'required' => ['title'],
        ]);
    }

    public function execute(array $params): ToolResult
    {
        $postId = wp_insert_post([
            'post_title'   => sanitize_text_field($params['title']),
            'post_content' => wp_kses_post($params['content'] ?? ''),
            'post_name'    => sanitize_title($params['slug'] ?? $params['title']),
            'post_parent'  => (int) ($params['parent_id'] ?? 0),
            'post_status'  => in_array($params['status'] ?? '', ['publish', 'draft'])
                                ? $params['status'] : 'draft',
            'post_type'    => 'page',
        ], true);

        if (is_wp_error($postId)) {
            return ToolResult::error($postId->get_error_message());
        }

        return ToolResult::success(
            message: "Page created: {$params['title']}",
            data: ['post_id' => $postId, 'edit_url' => get_edit_post_link($postId)],
        );
    }
}
