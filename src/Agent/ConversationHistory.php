<?php

declare(strict_types=1);

namespace WpAiAgent\Agent;

final class ConversationHistory
{
    /** @param array<array{role:string, content:string|array}> $messages */
    private function __construct(
        private readonly array $messages,
    ) {}

    public static function create(): self
    {
        return new self([]);
    }

    /** @param array<array{role:string, content:string|array}> $messages */
    public static function fromMessages(array $messages): self
    {
        return new self($messages);
    }

    public function addUserMessage(string|array $content): self
    {
        return new self([...$this->messages, ['role' => 'user', 'content' => $content]]);
    }

    public function addAssistantMessage(string|array $content): self
    {
        return new self([...$this->messages, ['role' => 'assistant', 'content' => $content]]);
    }

    /** @return array<array{role:string, content:string|array}> */
    public function toArray(): array
    {
        return $this->messages;
    }

    public function count(): int
    {
        return count($this->messages);
    }

    public function isEmpty(): bool
    {
        return $this->messages === [];
    }

    /** @return array{role:string, content:string|array}|null */
    public function last(): ?array
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->messages[array_key_last($this->messages)];
    }
}
