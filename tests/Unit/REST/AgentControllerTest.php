<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\REST;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use WpAiAgent\REST\AgentController;
use WpAiAgent\Agent\AgentOrchestrator;
use WpAiAgent\AI\Contracts\AIProviderInterface;
use WpAiAgent\Tools\ToolRegistry;
use WpAiAgent\Events\EventBus;
use WpAiAgent\DTO\AgentResult;

final class AgentControllerTest extends TestCase
{
    private AgentController $controller;
    private AIProviderInterface $ai;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\when('do_action')->justReturn();

        $this->ai = $this->createMock(AIProviderInterface::class);
        $orchestrator = new AgentOrchestrator(
            $this->ai,
            new ToolRegistry(),
            new EventBus(),
        );
        $this->controller = new AgentController($orchestrator);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRegisterRegistersAllRoutes(): void
    {
        $registeredRoutes = [];

        Functions\when('register_rest_route')->alias(
            function (string $namespace, string $route, array $args) use (&$registeredRoutes): void {
                $registeredRoutes[] = "{$namespace}{$route}";
            }
        );

        $this->controller->register();

        $this->assertContains('wp-ai-agent/v1/run', $registeredRoutes);
        $this->assertContains('wp-ai-agent/v1/status/(?P<run_id>[a-zA-Z0-9_.-]+)', $registeredRoutes);
        $this->assertContains('wp-ai-agent/v1/history', $registeredRoutes);
        $this->assertContains('wp-ai-agent/v1/acf-groups', $registeredRoutes);
    }

    public function testRegisterRequiresEditPagesCapability(): void
    {
        $permissionCallbacks = [];

        Functions\when('register_rest_route')->alias(
            function (string $namespace, string $route, array $args) use (&$permissionCallbacks): void {
                $permissionCallbacks[$route] = $args['permission_callback'];
            }
        );

        $this->controller->register();

        foreach ($permissionCallbacks as $route => $callback) {
            Functions\expect('current_user_can')
                ->once()
                ->with('edit_pages')
                ->andReturn(true);

            $this->assertTrue($callback(), "Permission callback for {$route} should delegate to current_user_can('edit_pages')");
        }
    }

    public function testHandleSuccessfulRun(): void
    {
        $this->ai->method('sendMessage')->willReturn([
            'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => 'Done.']],
        ]);

        $request = Mockery::mock(\WP_REST_Request::class);
        $request->shouldReceive('get_params')->once()->andReturn([
            'documentation' => 'Test docs',
            'goals' => 'Create a page',
            'target_url' => 'https://example.com',
        ]);

        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('uniqid')->justReturn('run_abc123');
        Functions\when('set_transient')->justReturn(true);
        Functions\when('get_option')->justReturn([]);
        Functions\when('update_option')->justReturn(true);
        Functions\when('rest_ensure_response')->alias(
            fn($data) => new \WP_REST_Response($data, 200)
        );

        $response = $this->controller->handle($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $this->assertSame('run_abc123', $response->get_data()['run_id']);
        $this->assertTrue($response->get_data()['success']);
    }

    public function testHandleMissingDocumentationReturns400(): void
    {
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->shouldReceive('get_params')->once()->andReturn([
            'goals' => 'Create a page',
        ]);

        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();

        $response = $this->controller->handle($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertSame(400, $response->get_status());
        $this->assertArrayHasKey('error', $response->get_data());
    }

    public function testHandleMissingGoalsReturns400(): void
    {
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->shouldReceive('get_params')->once()->andReturn([
            'documentation' => 'Some docs',
        ]);

        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();

        $response = $this->controller->handle($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertSame(400, $response->get_status());
    }

    public function testHandleOrchestratorExceptionReturns500(): void
    {
        $this->ai->method('sendMessage')->willThrowException(
            new \RuntimeException('API connection failed')
        );

        $request = Mockery::mock(\WP_REST_Request::class);
        $request->shouldReceive('get_params')->once()->andReturn([
            'documentation' => 'Test docs',
            'goals' => 'Create a page',
        ]);

        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('uniqid')->justReturn('run_fail');
        Functions\when('set_transient')->justReturn(true);

        $response = $this->controller->handle($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertSame(500, $response->get_status());
        $this->assertStringContainsString('API connection failed', $response->get_data()['error']);
    }

    public function testGetStatusReturnsTransientData(): void
    {
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->shouldReceive('get_param')->with('run_id')->once()->andReturn('run_abc123');

        $transientData = ['log' => ['[create_page] Done'], 'finished' => true];
        Functions\expect('get_transient')
            ->once()
            ->with('wp_ai_agent_run_run_abc123')
            ->andReturn($transientData);

        Functions\expect('rest_ensure_response')
            ->once()
            ->with($transientData)
            ->andReturnUsing(fn($data) => new \WP_REST_Response($data, 200));

        $response = $this->controller->getStatus($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $this->assertTrue($response->get_data()['finished']);
    }

    public function testGetStatusReturns404WhenNotFound(): void
    {
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->shouldReceive('get_param')->with('run_id')->once()->andReturn('run_nonexistent');

        Functions\expect('get_transient')
            ->once()
            ->with('wp_ai_agent_run_run_nonexistent')
            ->andReturn(false);

        $response = $this->controller->getStatus($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertSame(404, $response->get_status());
        $this->assertSame('Run not found', $response->get_data()['error']);
    }

    public function testGetHistoryReturnsReversedList(): void
    {
        $request = Mockery::mock(\WP_REST_Request::class);

        $history = [
            ['run_id' => 'run_1', 'created_at' => 1000],
            ['run_id' => 'run_2', 'created_at' => 2000],
            ['run_id' => 'run_3', 'created_at' => 3000],
        ];

        Functions\expect('get_option')
            ->once()
            ->with('wp_ai_agent_history', [])
            ->andReturn($history);

        Functions\expect('rest_ensure_response')
            ->once()
            ->andReturnUsing(fn($data) => new \WP_REST_Response($data, 200));

        $response = $this->controller->getHistory($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertCount(3, $data);
        $this->assertSame('run_3', $data[0]['run_id']);
        $this->assertSame('run_1', $data[2]['run_id']);
    }

    public function testGetHistoryReturnsEmptyArrayWhenNoHistory(): void
    {
        $request = Mockery::mock(\WP_REST_Request::class);

        Functions\expect('get_option')
            ->once()
            ->with('wp_ai_agent_history', [])
            ->andReturn([]);

        Functions\expect('rest_ensure_response')
            ->once()
            ->andReturnUsing(fn($data) => new \WP_REST_Response($data, 200));

        $response = $this->controller->getHistory($request);

        $this->assertSame([], $response->get_data());
    }

    public function testGetAcfGroupsReturnsEmptyWhenAcfNotActive(): void
    {
        $request = Mockery::mock(\WP_REST_Request::class);

        Functions\expect('rest_ensure_response')
            ->once()
            ->with([])
            ->andReturnUsing(fn($data) => new \WP_REST_Response($data, 200));

        $response = $this->controller->getAcfGroups($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertSame([], $response->get_data());
    }

    public function testGetAcfGroupsReturnsMappedGroups(): void
    {
        $request = Mockery::mock(\WP_REST_Request::class);

        Functions\when('acf_get_field_groups')->justReturn([
            ['key' => 'group_abc', 'title' => 'Hero Section'],
        ]);
        Functions\when('acf_get_fields')->justReturn([
            ['key' => 'field_1', 'name' => 'heading', 'type' => 'text', 'label' => 'Heading'],
        ]);

        Functions\expect('rest_ensure_response')
            ->once()
            ->andReturnUsing(fn($data) => new \WP_REST_Response($data, 200));

        $response = $this->controller->getAcfGroups($request);

        $data = $response->get_data();
        $this->assertCount(1, $data);
        $this->assertSame('group_abc', $data[0]['key']);
        $this->assertSame('Hero Section', $data[0]['title']);
        $this->assertCount(1, $data[0]['fields']);
        $this->assertSame('heading', $data[0]['fields'][0]['name']);
    }

    public function testHandleStoresResultInTransient(): void
    {
        $this->ai->method('sendMessage')->willReturn([
            'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => 'Done.']],
        ]);

        $request = Mockery::mock(\WP_REST_Request::class);
        $request->shouldReceive('get_params')->once()->andReturn([
            'documentation' => 'Docs',
            'goals' => 'Goals',
        ]);

        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('uniqid')->justReturn('run_xyz');
        Functions\when('get_option')->justReturn([]);
        Functions\when('update_option')->justReturn(true);

        $transientCalls = [];
        Functions\when('set_transient')->alias(
            function (string $key, $value, int $expiration) use (&$transientCalls): bool {
                $transientCalls[] = ['key' => $key, 'value' => $value];
                return true;
            }
        );

        Functions\when('rest_ensure_response')->alias(
            fn($data) => new \WP_REST_Response($data, 200)
        );

        $this->controller->handle($request);

        $this->assertCount(2, $transientCalls);

        $this->assertSame('wp_ai_agent_run_run_xyz', $transientCalls[0]['key']);
        $this->assertFalse($transientCalls[0]['value']['finished']);

        $this->assertSame('wp_ai_agent_run_run_xyz', $transientCalls[1]['key']);
        $this->assertTrue($transientCalls[1]['value']['finished']);
        $this->assertTrue($transientCalls[1]['value']['success']);
        $this->assertSame(1, $transientCalls[1]['value']['rounds']);
    }

    public function testHandleKeepsOnly50HistoryEntries(): void
    {
        $this->ai->method('sendMessage')->willReturn([
            'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => 'Done.']],
        ]);

        $request = Mockery::mock(\WP_REST_Request::class);
        $request->shouldReceive('get_params')->once()->andReturn([
            'documentation' => 'Docs',
            'goals' => 'Goals',
        ]);

        Functions\when('sanitize_textarea_field')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('uniqid')->justReturn('run_new');
        Functions\when('set_transient')->justReturn(true);

        $existingHistory = array_fill(0, 50, ['run_id' => 'run_old']);
        Functions\when('get_option')->justReturn($existingHistory);

        $savedHistory = null;
        Functions\when('update_option')->alias(
            function (string $key, $value) use (&$savedHistory): bool {
                $savedHistory = $value;
                return true;
            }
        );

        Functions\when('rest_ensure_response')->alias(
            fn($data) => new \WP_REST_Response($data, 200)
        );

        $this->controller->handle($request);

        $this->assertCount(50, $savedHistory);
        $this->assertSame('run_new', $savedHistory[49]['run_id']);
    }
}
