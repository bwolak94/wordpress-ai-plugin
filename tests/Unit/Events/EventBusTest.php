<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WpAiAgent\DTO\AgentResult;
use WpAiAgent\Events\AgentFinished;
use WpAiAgent\Events\EventBus;
use WpAiAgent\Events\PageCreated;
use WpAiAgent\Events\ToolExecuted;
use WpAiAgent\Tools\ToolResult;

final class EventBusTest extends TestCase
{
    private EventBus $bus;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->bus = new EventBus();
    }

    private function stubDoAction(): void
    {
        Functions\when('do_action')->justReturn();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testListenerReceivesDispatchedEvent(): void
    {
        $this->stubDoAction();
        $received = null;
        $event = new PageCreated(postId: 42, title: 'Home');

        $this->bus->listen(PageCreated::class, function (PageCreated $e) use (&$received): void {
            $received = $e;
        });
        $this->bus->dispatch($event);

        $this->assertSame($event, $received);
    }

    public function testMultipleListenersAllCalled(): void
    {
        $this->stubDoAction();
        $calls = 0;

        $this->bus->listen(PageCreated::class, function () use (&$calls): void {
            $calls++;
        });
        $this->bus->listen(PageCreated::class, function () use (&$calls): void {
            $calls++;
        });

        $this->bus->dispatch(new PageCreated(postId: 1, title: 'Test'));

        $this->assertSame(2, $calls);
    }

    public function testDispatchWithNoListenersDoesNotThrow(): void
    {
        $this->stubDoAction();
        $this->bus->dispatch(new PageCreated(postId: 1, title: 'Orphan'));

        $this->assertTrue(true);
    }

    public function testListenersOnlyCalledForMatchingEventClass(): void
    {
        $this->stubDoAction();
        $pageCalled = false;
        $toolCalled = false;

        $this->bus->listen(PageCreated::class, function () use (&$pageCalled): void {
            $pageCalled = true;
        });
        $this->bus->listen(ToolExecuted::class, function () use (&$toolCalled): void {
            $toolCalled = true;
        });

        $this->bus->dispatch(new PageCreated(postId: 1, title: 'Test'));

        $this->assertTrue($pageCalled);
        $this->assertFalse($toolCalled);
    }

    public function testDoActionCalledOnDispatch(): void
    {
        $event = new PageCreated(postId: 1, title: 'Test');

        $actionsCalled = [];
        Functions\when('do_action')->alias(function (string $tag) use (&$actionsCalled): void {
            $actionsCalled[] = $tag;
        });

        $this->bus->dispatch($event);

        $this->assertContains('wp_ai_agent_event', $actionsCalled);
        $this->assertContains('wp_ai_agent_pagecreated', $actionsCalled);
    }

    public function testToolExecutedEvent(): void
    {
        $this->stubDoAction();
        $received = null;
        $toolResult = ToolResult::success('done', ['id' => 5]);
        $event = new ToolExecuted(
            toolName: 'create_page',
            input: ['title' => 'Hello'],
            result: $toolResult,
        );

        $this->bus->listen(ToolExecuted::class, function (ToolExecuted $e) use (&$received): void {
            $received = $e;
        });
        $this->bus->dispatch($event);

        $this->assertSame('create_page', $received->toolName);
        $this->assertSame(['title' => 'Hello'], $received->input);
        $this->assertSame($toolResult, $received->result);
    }

    public function testAgentFinishedEvent(): void
    {
        $this->stubDoAction();
        $received = null;
        $agentResult = new AgentResult(log: ['step1'], rounds: 2, pages: [], success: true);
        $event = new AgentFinished(result: $agentResult);

        $this->bus->listen(AgentFinished::class, function (AgentFinished $e) use (&$received): void {
            $received = $e;
        });
        $this->bus->dispatch($event);

        $this->assertSame($agentResult, $received->result);
    }

    public function testSeparateInstancesHaveIndependentListeners(): void
    {
        $this->stubDoAction();
        $bus2 = new EventBus();
        $called = false;

        $this->bus->listen(PageCreated::class, function () use (&$called): void {
            $called = true;
        });

        $bus2->dispatch(new PageCreated(postId: 1, title: 'Test'));

        $this->assertFalse($called);
    }
}
