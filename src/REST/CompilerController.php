<?php

declare(strict_types=1);

namespace WpAiAgent\REST;

use WpAiAgent\Acf\AcfVersionDetector;
use WpAiAgent\Analysis\HtmlAnalyzerService;

final class CompilerController
{
    public function __construct(
        private readonly HtmlAnalyzerService $analyzer,
        private readonly AcfVersionDetector  $detector,
    ) {}

    public function register(): void
    {
        register_rest_route('wp-ai-agent/v1', '/compile', [
            'methods'             => 'POST',
            'callback'            => [$this, 'compile'],
            'permission_callback' => fn(): bool => current_user_can('edit_pages'),
            'args'                => [
                'html'     => ['required' => true, 'type' => 'string'],
                'template' => ['type' => 'string', 'default' => ''],
                'prefix'   => ['type' => 'string', 'default' => 'page_'],
                'page_id'  => ['type' => 'integer'],
            ],
        ]);

        register_rest_route('wp-ai-agent/v1', '/acf-status', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getAcfStatus'],
            'permission_callback' => fn(): bool => current_user_can('edit_pages'),
        ]);

        register_rest_route('wp-ai-agent/v1', '/compiler-history', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getHistory'],
            'permission_callback' => fn(): bool => current_user_can('edit_pages'),
        ]);
    }

    public function compile(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $html     = $request->get_param('html');
            $template = $request->get_param('template') ?? '';
            $prefix   = $request->get_param('prefix') ?? 'page_';

            $result = $this->analyzer->analyze($html, $template);

            if ($result->success) {
                $history   = get_option('wp_ai_compiler_history', []);
                $history[] = array_merge($result->toArray(), [
                    'prefix'     => $prefix,
                    'created_at' => time(),
                ]);
                update_option('wp_ai_compiler_history', array_slice($history, -50));
            }

            return rest_ensure_response($result->toArray());
        } catch (\Throwable $e) {
            return new \WP_REST_Response(['error' => 'Compilation failed: ' . $e->getMessage()], 500);
        }
    }

    public function getAcfStatus(\WP_REST_Request $request): \WP_REST_Response
    {
        return rest_ensure_response([
            'acf_active'  => $this->detector->isActive(),
            'acf_pro'     => $this->detector->isPro(),
            'acf_version' => $this->detector->getVersion(),
            'field_types' => $this->detector->getAvailableFieldTypes(),
        ]);
    }

    public function getHistory(\WP_REST_Request $request): \WP_REST_Response
    {
        $history = get_option('wp_ai_compiler_history', []);

        return rest_ensure_response(array_reverse($history));
    }
}
