<?php

declare(strict_types=1);

namespace WpAiAgent;

use WpAiAgent\Admin\AdminPage;
use WpAiAgent\Agent\AgentOrchestrator;
use WpAiAgent\AI\Providers\ClaudeProvider;
use WpAiAgent\Events\EventBus;
use WpAiAgent\Events\ToolExecuted;
use WpAiAgent\REST\AgentController;
use WpAiAgent\Tools\ToolRegistry;
use WpAiAgent\Tools\Implementations\CreatePageTool;
use WpAiAgent\Tools\Implementations\SetAcfFieldTool;
use WpAiAgent\Tools\Implementations\UploadMediaTool;
use WpAiAgent\Tools\Implementations\SetMenuItemTool;
use WpAiAgent\Tools\Implementations\GetAcfSchemaTool;

final class Plugin
{
    public static function boot(): void
    {
        $instance = new self();
        add_action('rest_api_init', [$instance, 'registerRoutes']);
        add_action('admin_menu', [$instance, 'registerAdmin']);
    }

    public function registerRoutes(): void
    {
        $this->buildController()->register();
    }

    public function registerAdmin(): void
    {
        (new AdminPage())->register();
    }

    private function buildController(): AgentController
    {
        $model = get_option('wp_ai_agent_model', 'claude-opus-4-5');

        $provider = new ClaudeProvider(
            apiKey: defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '',
            model: $model,
        );

        $registry = (new ToolRegistry())
            ->register(new CreatePageTool())
            ->register(new SetAcfFieldTool())
            ->register(new UploadMediaTool())
            ->register(new SetMenuItemTool())
            ->register(new GetAcfSchemaTool());

        $bus = new EventBus();

        $bus->listen(ToolExecuted::class, function (ToolExecuted $e): void {
            $status = $e->result->success ? 'OK' : 'ERROR';
            error_log("[WpAiAgent] {$e->toolName} [{$status}]: {$e->result->message}");
        });

        $orchestrator = new AgentOrchestrator($provider, $registry, $bus);

        return new AgentController($orchestrator);
    }
}
