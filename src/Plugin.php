<?php

declare(strict_types=1);

namespace WpAiAgent;

use WpAiAgent\Acf\AcfFieldFactory;
use WpAiAgent\Acf\AcfVersionDetector;
use WpAiAgent\Acf\ProFieldUpgrader;
use WpAiAgent\Admin\AdminPage;
use WpAiAgent\Agent\AgentOrchestrator;
use WpAiAgent\AI\Providers\ClaudeProvider;
use WpAiAgent\Analysis\HtmlAnalyzerService;
use WpAiAgent\Events\EventBus;
use WpAiAgent\Events\ToolExecuted;
use WpAiAgent\REST\AgentController;
use WpAiAgent\REST\CompilerController;
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

        $detector = new AcfVersionDetector();
        if (!$detector->isActive()) {
            add_action('admin_notices', [self::class, 'renderAcfMissingNotice']);
        }
    }

    public function registerRoutes(): void
    {
        $this->buildAgentController()->register();
        $this->buildCompilerController()->register();
    }

    public function registerAdmin(): void
    {
        (new AdminPage())->register();
    }

    public static function renderAcfMissingNotice(): void
    {
        echo '<div class="notice notice-warning"><p>'
           . '<strong>WP AI Agent:</strong> Advanced Custom Fields (ACF) plugin is not active. '
           . 'The HTML compiler requires ACF to generate field groups.'
           . '</p></div>';
    }

    private function buildAgentController(): AgentController
    {
        $provider = $this->buildProvider();

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

    private function buildCompilerController(): CompilerController
    {
        $provider        = $this->buildProvider();
        $detector        = new AcfVersionDetector();
        $upgrader        = new ProFieldUpgrader();
        $fieldFactory    = new AcfFieldFactory($detector, $upgrader);
        $analyzerService = new HtmlAnalyzerService($provider, $detector, $fieldFactory);

        return new CompilerController($analyzerService, $detector);
    }

    private function buildProvider(): ClaudeProvider
    {
        $model = get_option('wp_ai_agent_model', 'claude-opus-4-5');

        return new ClaudeProvider(
            apiKey: defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '',
            model: $model,
        );
    }
}
