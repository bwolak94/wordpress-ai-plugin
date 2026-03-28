<?php

declare(strict_types=1);

namespace WpAiAgent\Events;

final class PageCreated
{
    public function __construct(
        public readonly int $postId,
        public readonly string $title,
        public readonly array $data = [],
    ) {}
}
