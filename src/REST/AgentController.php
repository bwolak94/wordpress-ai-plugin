<?php

declare(strict_types=1);

namespace WpAiAgent\REST;

use WpAiAgent\Agent\AgentOrchestrator;
use WpAiAgent\DTO\Brief;

final class AgentController
{
    public function __construct(
        private readonly AgentOrchestrator $orchestrator,
    ) {}

    public function register(): void
    {
        register_rest_route('wp-ai-agent/v1', '/run', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle'],
            'permission_callback' => fn(): bool => current_user_can('edit_pages'),
            'args'                => [
                'documentation' => ['required' => true, 'type' => 'string'],
                'goals'         => ['required' => true, 'type' => 'string'],
                'target_url'    => ['type' => 'string'],
                'acf_group_key' => ['type' => 'string'],
                'parent_id'     => ['type' => 'integer'],
                'model'         => ['type' => 'string', 'enum' => ['claude-opus-4-5', 'claude-sonnet-4-6']],
            ],
        ]);

        register_rest_route('wp-ai-agent/v1', '/status/(?P<run_id>[a-zA-Z0-9_.-]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getStatus'],
            'permission_callback' => fn(): bool => current_user_can('edit_pages'),
        ]);

        register_rest_route('wp-ai-agent/v1', '/history', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getHistory'],
            'permission_callback' => fn(): bool => current_user_can('edit_pages'),
        ]);

        register_rest_route('wp-ai-agent/v1', '/acf-groups', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getAcfGroups'],
            'permission_callback' => fn(): bool => current_user_can('edit_pages'),
        ]);
    }

    public function handle(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $brief = Brief::fromArray($request->get_params());
            $runId = uniqid('run_', true);

            set_transient("wp_ai_agent_run_{$runId}", ['log' => [], 'finished' => false], HOUR_IN_SECONDS);

            $result = $this->orchestrator->run($brief);

            set_transient("wp_ai_agent_run_{$runId}", [
                'log'      => $result->log,
                'finished' => true,
                'pages'    => $result->pages,
                'rounds'   => $result->rounds,
                'success'  => $result->success,
            ], DAY_IN_SECONDS);

            $history   = get_option('wp_ai_agent_history', []);
            $history[] = array_merge($result->toArray(), ['run_id' => $runId, 'created_at' => time()]);
            update_option('wp_ai_agent_history', array_slice($history, -50));

            return rest_ensure_response(array_merge($result->toArray(), ['run_id' => $runId]));
        } catch (\InvalidArgumentException $e) {
            return new \WP_REST_Response(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return new \WP_REST_Response(['error' => 'Agent failed: ' . $e->getMessage()], 500);
        }
    }

    public function getStatus(\WP_REST_Request $request): \WP_REST_Response
    {
        $runId = $request->get_param('run_id');
        $data  = get_transient("wp_ai_agent_run_{$runId}");

        if ($data === false) {
            return new \WP_REST_Response(['error' => 'Run not found'], 404);
        }

        return rest_ensure_response($data);
    }

    public function getHistory(\WP_REST_Request $request): \WP_REST_Response
    {
        $history = get_option('wp_ai_agent_history', []);

        return rest_ensure_response(array_reverse($history));
    }

    public function getAcfGroups(\WP_REST_Request $request): \WP_REST_Response
    {
        if (!function_exists('acf_get_field_groups')) {
            return rest_ensure_response([]);
        }

        $groups = array_map(fn(array $g): array => [
            'key'    => $g['key'],
            'title'  => $g['title'],
            'fields' => array_map(fn(array $f): array => [
                'key'   => $f['key'],
                'name'  => $f['name'],
                'type'  => $f['type'],
                'label' => $f['label'],
            ], acf_get_fields($g['key']) ?: []),
        ], acf_get_field_groups());

        return rest_ensure_response($groups);
    }
}
