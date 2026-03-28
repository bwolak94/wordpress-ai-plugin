<?php
/**
 * Plugin Name: WP AI Page Builder
 * Description: Autonomous AI agent that builds WordPress pages from documentation.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Requires at least: 6.0
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

define('WP_AI_AGENT_VERSION', '1.0.0');
define('WP_AI_AGENT_DIR', plugin_dir_path(__FILE__));

require_once WP_AI_AGENT_DIR . 'vendor/autoload.php';

// Load API key from wp-config.php or .env
if (file_exists(WP_AI_AGENT_DIR . '.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(WP_AI_AGENT_DIR);
    $dotenv->safeLoad();
    if (!defined('ANTHROPIC_API_KEY') && isset($_ENV['ANTHROPIC_API_KEY'])) {
        define('ANTHROPIC_API_KEY', $_ENV['ANTHROPIC_API_KEY']);
    }
}

add_action('plugins_loaded', fn() => \WpAiAgent\Plugin::boot());
