<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\Agent;

use PHPUnit\Framework\TestCase;
use WpAiAgent\Agent\ConversationHistory;

final class ConversationHistoryTest extends TestCase
{
    public function testCreateReturnsEmptyHistory(): void
    {
        $history = ConversationHistory::create();

        $this->assertTrue($history->isEmpty());
        $this->assertSame(0, $history->count());
        $this->assertSame([], $history->toArray());
        $this->assertNull($history->last());
    }

    public function testFromMessagesRestoresHistory(): void
    {
        $messages = [
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi there'],
        ];

        $history = ConversationHistory::fromMessages($messages);

        $this->assertSame(2, $history->count());
        $this->assertSame($messages, $history->toArray());
    }

    public function testAddUserMessageReturnsNewInstance(): void
    {
        $original = ConversationHistory::create();
        $updated = $original->addUserMessage('Hello');

        $this->assertTrue($original->isEmpty());
        $this->assertSame(1, $updated->count());
        $this->assertNotSame($original, $updated);
    }

    public function testAddAssistantMessageReturnsNewInstance(): void
    {
        $original = ConversationHistory::create()->addUserMessage('Hi');
        $updated = $original->addAssistantMessage('Hello back');

        $this->assertSame(1, $original->count());
        $this->assertSame(2, $updated->count());
        $this->assertNotSame($original, $updated);
    }

    public function testImmutabilityAcrossMultipleAdds(): void
    {
        $h0 = ConversationHistory::create();
        $h1 = $h0->addUserMessage('msg1');
        $h2 = $h1->addAssistantMessage('msg2');
        $h3 = $h2->addUserMessage('msg3');

        $this->assertSame(0, $h0->count());
        $this->assertSame(1, $h1->count());
        $this->assertSame(2, $h2->count());
        $this->assertSame(3, $h3->count());
    }

    public function testAddUserMessageWithArrayContent(): void
    {
        $toolResults = [
            ['type' => 'tool_result', 'tool_use_id' => 'tu_1', 'content' => '{"success":true}'],
        ];

        $history = ConversationHistory::create()->addUserMessage($toolResults);
        $last = $history->last();

        $this->assertSame('user', $last['role']);
        $this->assertSame($toolResults, $last['content']);
    }

    public function testAddAssistantMessageWithArrayContent(): void
    {
        $blocks = [
            ['type' => 'text', 'text' => 'Let me create a page'],
            ['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'create_page', 'input' => ['title' => 'Test']],
        ];

        $history = ConversationHistory::create()
            ->addUserMessage('Create a page')
            ->addAssistantMessage($blocks);

        $last = $history->last();

        $this->assertSame('assistant', $last['role']);
        $this->assertSame($blocks, $last['content']);
    }

    public function testLastReturnsLastMessage(): void
    {
        $history = ConversationHistory::create()
            ->addUserMessage('first')
            ->addAssistantMessage('second')
            ->addUserMessage('third');

        $last = $history->last();

        $this->assertSame('user', $last['role']);
        $this->assertSame('third', $last['content']);
    }

    public function testToArrayPreservesOrder(): void
    {
        $history = ConversationHistory::create()
            ->addUserMessage('a')
            ->addAssistantMessage('b')
            ->addUserMessage('c');

        $arr = $history->toArray();

        $this->assertSame('a', $arr[0]['content']);
        $this->assertSame('b', $arr[1]['content']);
        $this->assertSame('c', $arr[2]['content']);
    }
}
