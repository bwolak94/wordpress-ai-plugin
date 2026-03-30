<?php

declare(strict_types=1);

namespace WpAiAgent\Admin;

final class AdminPage
{
    public function register(): void
    {
        $this->addMenuPage();
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addMenuPage(): void
    {
        add_menu_page(
            'AI Page Builder',
            'AI Builder',
            'edit_pages',
            'wp-ai-agent',
            [$this, 'renderRoot'],
            'dashicons-superhero',
            25,
        );
    }

    public function renderRoot(): void
    {
        echo '<div id="wp-ai-agent-root"></div>';
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'toplevel_page_wp-ai-agent') {
            return;
        }

        $manifestPath = WP_AI_AGENT_DIR . 'assets/build/.vite/manifest.json';

        if (!file_exists($manifestPath)) {
            wp_enqueue_script(
                'wp-ai-agent',
                'http://localhost:5173/src/main.tsx',
                [],
                null,
                true,
            );
        } else {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $entry = $manifest['assets/src/main.tsx'] ?? $manifest['src/main.tsx'] ?? null;

            if (!$entry) {
                return;
            }

            wp_enqueue_script(
                'wp-ai-agent',
                plugins_url('assets/build/' . $entry['file'], WP_AI_AGENT_DIR . 'wp-ai-page-builder.php'),
                [],
                WP_AI_AGENT_VERSION,
                true,
            );

            foreach ($entry['css'] ?? [] as $cssFile) {
                wp_enqueue_style(
                    'wp-ai-agent-' . md5($cssFile),
                    plugins_url('assets/build/' . $cssFile, WP_AI_AGENT_DIR . 'wp-ai-page-builder.php'),
                    [],
                    WP_AI_AGENT_VERSION,
                );
            }
        }

        wp_add_inline_script(
            'wp-ai-agent',
            'window.wpAiAgent = ' . json_encode([
                'nonce' => wp_create_nonce('wp_rest'),
                'root' => rest_url(),
                'adminUrl' => admin_url(),
                'version' => WP_AI_AGENT_VERSION,
                'userCaps' => ['edit_pages' => current_user_can('edit_pages')],
            ]),
            'before',
        );
    }
}
