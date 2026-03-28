<?php

declare(strict_types=1);

namespace WpAiAgent\Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use WpAiAgent\DTO\AgentResult;

final class AgentResultTest extends TestCase
{
    public function testToArrayReturnsCorrectShape(): void
    {
        $result = new AgentResult(
            log: ['Step 1', 'Step 2'],
            rounds: 3,
            pages: [['id' => 1, 'title' => 'Home']],
            success: true,
        );

        $this->assertSame([
            'success' => true,
            'rounds'  => 3,
            'log'     => ['Step 1', 'Step 2'],
            'pages'   => [['id' => 1, 'title' => 'Home']],
        ], $result->toArray());
    }

    public function testDefaultValues(): void
    {
        $result = new AgentResult(log: [], rounds: 0);

        $this->assertTrue($result->success);
        $this->assertSame([], $result->pages);
        $this->assertSame(0, $result->rounds);
    }

    public function testFailureResult(): void
    {
        $result = new AgentResult(
            log: ['Error occurred'],
            rounds: 1,
            success: false,
        );

        $array = $result->toArray();
        $this->assertFalse($array['success']);
        $this->assertSame(1, $array['rounds']);
        $this->assertSame(['Error occurred'], $array['log']);
        $this->assertSame([], $array['pages']);
    }
}
