<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WpAiAgent\Admin\AdminPage;

final class AdminPageTest extends TestCase
{
    private AdminPage $adminPage;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->adminPage = new AdminPage();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRegisterAddsMenuAndEnqueueHooks(): void
    {
        $hooks = [];
        Functions\when('add_action')->alias(
            function (string $hook, $callback) use (&$hooks): void {
                $hooks[] = $hook;
            }
        );

        $this->adminPage->register();

        $this->assertContains('admin_menu', $hooks);
        $this->assertContains('admin_enqueue_scripts', $hooks);
    }

    public function testAddMenuPageCallsWpAddMenuPage(): void
    {
        $capturedArgs = null;

        Functions\expect('add_menu_page')
            ->once()
            ->andReturnUsing(function () use (&$capturedArgs): string {
                $capturedArgs = func_get_args();
                return 'hook_suffix';
            });

        $this->adminPage->addMenuPage();

        $this->assertSame('AI Page Builder', $capturedArgs[0]);
        $this->assertSame('AI Builder', $capturedArgs[1]);
        $this->assertSame('edit_pages', $capturedArgs[2]);
        $this->assertSame('wp-ai-agent', $capturedArgs[3]);
        $this->assertSame('dashicons-superhero', $capturedArgs[5]);
        $this->assertSame(25, $capturedArgs[6]);
    }

    public function testRenderRootOutputsDivElement(): void
    {
        ob_start();
        $this->adminPage->renderRoot();
        $output = ob_get_clean();

        $this->assertSame('<div id="wp-ai-agent-root"></div>', $output);
    }

    public function testEnqueueAssetsSkipsNonPluginPages(): void
    {
        $scriptEnqueued = false;
        Functions\when('wp_enqueue_script')->alias(function () use (&$scriptEnqueued): void {
            $scriptEnqueued = true;
        });

        $this->adminPage->enqueueAssets('edit.php');

        $this->assertFalse($scriptEnqueued);
    }

    public function testEnqueueAssetsDevModeWhenNoManifest(): void
    {
        Functions\when('file_exists')->justReturn(false);

        Functions\expect('wp_enqueue_script')
            ->once()
            ->andReturnUsing(function (string $handle, string $src): void {
                $this->assertSame('wp-ai-agent', $handle);
                $this->assertSame('http://localhost:5173/src/main.tsx', $src);
            });

        Functions\when('wp_create_nonce')->justReturn('test-nonce');
        Functions\when('rest_url')->justReturn('https://example.com/wp-json/');
        Functions\when('admin_url')->justReturn('https://example.com/wp-admin/');
        Functions\when('current_user_can')->justReturn(true);

        $inlineScript = null;
        Functions\when('wp_add_inline_script')->alias(
            function (string $handle, string $data, string $position) use (&$inlineScript): void {
                $inlineScript = $data;
            }
        );

        $this->adminPage->enqueueAssets('toplevel_page_wp-ai-agent');

        $this->assertNotNull($inlineScript);
        $this->assertStringContainsString('window.wpAiAgent', $inlineScript);
        $this->assertStringContainsString('test-nonce', $inlineScript);
    }

    public function testEnqueueAssetsProductionModeWithManifest(): void
    {
        $manifest = [
            'src/main.tsx' => [
                'file' => 'assets/main-abc123.js',
                'css' => ['assets/main-def456.css'],
            ],
        ];

        Functions\when('file_exists')->justReturn(true);
        Functions\when('file_get_contents')->justReturn(json_encode($manifest));

        $enqueuedScripts = [];
        Functions\when('wp_enqueue_script')->alias(
            function (string $handle, string $src) use (&$enqueuedScripts): void {
                $enqueuedScripts[$handle] = $src;
            }
        );

        $enqueuedStyles = [];
        Functions\when('wp_enqueue_style')->alias(
            function (string $handle, string $src) use (&$enqueuedStyles): void {
                $enqueuedStyles[$handle] = $src;
            }
        );

        Functions\when('plugin_dir_url')->justReturn('https://example.com/wp-content/plugins/wp-ai-agent/assets/build/');
        Functions\when('wp_create_nonce')->justReturn('nonce');
        Functions\when('rest_url')->justReturn('/wp-json/');
        Functions\when('admin_url')->justReturn('/wp-admin/');
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_add_inline_script')->justReturn();

        $this->adminPage->enqueueAssets('toplevel_page_wp-ai-agent');

        $this->assertArrayHasKey('wp-ai-agent', $enqueuedScripts);
        $this->assertStringContainsString('main-abc123.js', $enqueuedScripts['wp-ai-agent']);
        $this->assertCount(1, $enqueuedStyles);
    }

    public function testEnqueueAssetsExposesWindowObject(): void
    {
        Functions\when('file_exists')->justReturn(false);
        Functions\when('wp_enqueue_script')->justReturn();
        Functions\when('wp_create_nonce')->justReturn('rest-nonce');
        Functions\when('rest_url')->justReturn('https://example.com/wp-json/');
        Functions\when('admin_url')->justReturn('https://example.com/wp-admin/');
        Functions\when('current_user_can')->justReturn(true);

        $capturedPosition = null;
        $capturedData = null;
        Functions\when('wp_add_inline_script')->alias(
            function (string $handle, string $data, string $position) use (&$capturedData, &$capturedPosition): void {
                $capturedData = $data;
                $capturedPosition = $position;
            }
        );

        $this->adminPage->enqueueAssets('toplevel_page_wp-ai-agent');

        $this->assertSame('before', $capturedPosition);
        $this->assertStringStartsWith('window.wpAiAgent = ', $capturedData);

        $json = json_decode(substr($capturedData, strlen('window.wpAiAgent = ')), true);
        $this->assertSame('rest-nonce', $json['nonce']);
        $this->assertSame('https://example.com/wp-json/', $json['root']);
        $this->assertSame('https://example.com/wp-admin/', $json['adminUrl']);
        $this->assertSame('1.0.0', $json['version']);
        $this->assertTrue($json['userCaps']['edit_pages']);
    }

    public function testEnqueueAssetsReturnsEarlyWhenNoEntryInManifest(): void
    {
        Functions\when('file_exists')->justReturn(true);
        Functions\when('file_get_contents')->justReturn(json_encode(['other/entry.tsx' => []]));

        $scriptEnqueued = false;
        Functions\when('wp_enqueue_script')->alias(function () use (&$scriptEnqueued): void {
            $scriptEnqueued = true;
        });

        $this->adminPage->enqueueAssets('toplevel_page_wp-ai-agent');

        $this->assertFalse($scriptEnqueued);
    }
}
