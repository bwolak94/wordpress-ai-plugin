<?php

declare(strict_types=1);

namespace WpAiAgent\Tools;

final class ToolDefinition
{
    /** @param array<string, mixed> $inputSchema JSON Schema for parameters */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly array $inputSchema,
    ) {}

    /**
     * Fluent builder entry point.
     * Usage: ToolDefinition::make('create_page', 'Creates a WP page')->withSchema([...])
     */
    public static function make(string $name, string $description): self
    {
        return new self($name, $description, [
            'type'       => 'object',
            'properties' => [],
            'required'   => [],
        ]);
    }

    public function withSchema(array $schema): self
    {
        return new self($this->name, $this->description, $schema);
    }

    /** Export to Anthropic API format */
    public function toAnthropicFormat(): array
    {
        return [
            'name'         => $this->name,
            'description'  => $this->description,
            'input_schema' => $this->inputSchema,
        ];
    }
}
