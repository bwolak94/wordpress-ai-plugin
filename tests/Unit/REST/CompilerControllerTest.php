<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\REST;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WpAiAgent\Acf\AcfFieldFactory;
use WpAiAgent\Acf\AcfVersionDetector;
use WpAiAgent\Acf\ProFieldUpgrader;
use WpAiAgent\Analysis\HtmlAnalyzerService;
use WpAiAgent\REST\CompilerController;

final class CompilerControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    private function buildController(): CompilerController
    {
        $detector     = new AcfVersionDetector();
        $upgrader     = new ProFieldUpgrader();
        $fieldFactory = new AcfFieldFactory($detector, $upgrader);

        $mockProvider = $this->createMock(\WpAiAgent\AI\Contracts\AIProviderInterface::class);
        $analyzer     = new HtmlAnalyzerService($mockProvider, $detector, $fieldFactory);

        return new CompilerController($analyzer, $detector);
    }

    public function testRegisterRegistersThreeRoutes(): void
    {
        $routes = [];
        Functions\when('register_rest_route')->alias(
            function (string $ns, string $route) use (&$routes): void {
                $routes[] = $ns . $route;
            }
        );

        $this->buildController()->register();

        $this->assertContains('wp-ai-agent/v1/compile', $routes);
        $this->assertContains('wp-ai-agent/v1/acf-status', $routes);
        $this->assertContains('wp-ai-agent/v1/compiler-history', $routes);
    }

    public function testGetAcfStatusReturnsStatusData(): void
    {
        Functions\when('rest_ensure_response')->alias(function ($data) {
            return new \WP_REST_Response($data);
        });

        $controller = $this->buildController();
        $request    = new \WP_REST_Request();
        $response   = $controller->getAcfStatus($request);
        $data       = $response->get_data();

        $this->assertArrayHasKey('acf_active', $data);
        $this->assertArrayHasKey('acf_pro', $data);
        $this->assertArrayHasKey('acf_version', $data);
        $this->assertArrayHasKey('field_types', $data);
    }

    public function testGetHistoryReturnsEmptyArrayWhenNoHistory(): void
    {
        Functions\when('get_option')->justReturn([]);
        Functions\when('rest_ensure_response')->alias(function ($data) {
            return new \WP_REST_Response($data);
        });

        $controller = $this->buildController();
        $request    = new \WP_REST_Request();
        $response   = $controller->getHistory($request);

        $this->assertEmpty($response->get_data());
    }

    public function testCompileReturnsErrorWhenAcfNotActive(): void
    {
        Functions\when('rest_ensure_response')->alias(function ($data) {
            return new \WP_REST_Response($data);
        });

        $controller = $this->buildController();
        $request    = new \WP_REST_Request();
        $request->set_param('html', '<div>test</div>');
        $request->set_param('template', '');
        $request->set_param('prefix', 'page_');

        $response = $controller->compile($request);
        $data     = $response->get_data();

        // ACF is not active in test env, so analyze() returns error
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('ACF', $data['error']);
    }
}
