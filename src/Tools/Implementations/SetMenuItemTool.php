<?php

declare(strict_types=1);

namespace WpAiAgent\Tools\Implementations;

use WpAiAgent\Tools\Contracts\ToolInterface;
use WpAiAgent\Tools\ToolDefinition;
use WpAiAgent\Tools\ToolResult;

final class SetMenuItemTool implements ToolInterface
{
    public function getName(): string
    {
        return 'set_menu_item';
    }

    public function getDefinition(): ToolDefinition
    {
        return ToolDefinition::make(
            name: 'set_menu_item',
            description: 'Adds a page to a WordPress navigation menu. '
                . 'Use after create_page to link the new page into site navigation.'
        )->withSchema([
            'type' => 'object',
            'properties' => [
                'menu_location' => ['type' => 'string', 'description' => 'Theme menu location slug, e.g. "primary"'],
                'post_id'       => ['type' => 'integer', 'description' => 'Post ID to add to the menu'],
                'title'         => ['type' => 'string', 'description' => 'Menu item label (defaults to page title)'],
                'parent_item'   => ['type' => 'integer', 'description' => 'Parent menu item ID for nested menus'],
            ],
            'required' => ['menu_location', 'post_id'],
        ]);
    }

    public function execute(array $params): ToolResult
    {
        $locations = get_nav_menu_locations();
        $location  = sanitize_text_field($params['menu_location']);

        if (empty($locations[$location])) {
            return ToolResult::error("Menu location '{$location}' not found");
        }

        $menuId = $locations[$location];
        $postId = (int) $params['post_id'];

        $itemId = wp_update_nav_menu_item($menuId, 0, [
            'menu-item-object-id' => $postId,
            'menu-item-object'    => 'page',
            'menu-item-type'      => 'post_type',
            'menu-item-status'    => 'publish',
            'menu-item-title'     => sanitize_text_field($params['title'] ?? ''),
            'menu-item-parent-id' => (int) ($params['parent_item'] ?? 0),
        ]);

        if (is_wp_error($itemId)) {
            return ToolResult::error($itemId->get_error_message());
        }

        return ToolResult::success(
            message: "Page {$postId} added to menu '{$location}'",
            data: ['menu_item_id' => $itemId, 'menu_id' => $menuId],
        );
    }
}
