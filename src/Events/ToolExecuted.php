<?php

declare(strict_types=1);

namespace WpAiAgent\Events;

use WpAiAgent\Tools\ToolResult;

final class ToolExecuted
{
    public function __construct(
        public readonly string $toolName,
        public readonly array $input,
        public readonly ToolResult $result,
    ) {}
}
