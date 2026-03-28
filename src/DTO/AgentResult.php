<?php

declare(strict_types=1);

namespace WpAiAgent\DTO;

final class AgentResult
{
    public function __construct(
        public readonly array $log,
        public readonly int $rounds,
        public readonly array $pages = [],
        public readonly bool $success = true,
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'rounds'  => $this->rounds,
            'log'     => $this->log,
            'pages'   => $this->pages,
        ];
    }
}
