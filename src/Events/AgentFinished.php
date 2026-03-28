<?php

declare(strict_types=1);

namespace WpAiAgent\Events;

use WpAiAgent\DTO\AgentResult;

final class AgentFinished
{
    public function __construct(
        public readonly AgentResult $result,
    ) {}
}
