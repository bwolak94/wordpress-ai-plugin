<?php

declare(strict_types=1);

namespace WpAiAgent\Tools;

final class ToolResult
{
    private function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $data = [],
    ) {}

    public static function success(string $message, array $data = []): self
    {
        return new self(true, $message, $data);
    }

    public static function error(string $message): self
    {
        return new self(false, $message);
    }

    public function toJson(): string
    {
        return json_encode([
            'success' => $this->success,
            'message' => $this->message,
            'data'    => $this->data,
        ], JSON_THROW_ON_ERROR);
    }
}
