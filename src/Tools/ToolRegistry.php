<?php

declare(strict_types=1);

namespace WpAiAgent\Tools;

use WpAiAgent\Tools\Contracts\ToolInterface;

final class ToolRegistry
{
    /** @var array<string, ToolInterface> */
    private array $tools = [];

    /**
     * Register a tool. Returns $this for fluent chaining:
     * $registry->register(new CreatePageTool())->register(new SetAcfFieldTool())
     */
    public function register(ToolInterface $tool): self
    {
        $this->tools[$tool->getName()] = $tool;
        return $this;
    }

    /**
     * Dispatch a tool call by name with params from Claude.
     * Never throws — returns ToolResult::error() if tool not found or execution fails.
     */
    public function dispatch(string $name, array $params): ToolResult
    {
        if (!isset($this->tools[$name])) {
            return ToolResult::error("Unknown tool: {$name}");
        }

        try {
            return $this->tools[$name]->execute($params);
        } catch (\Throwable $e) {
            return ToolResult::error($e->getMessage());
        }
    }

    /**
     * Return all ToolDefinitions for sending to Claude API.
     *
     * @return ToolDefinition[]
     */
    public function getDefinitions(): array
    {
        return array_map(
            fn(ToolInterface $t) => $t->getDefinition(),
            array_values($this->tools)
        );
    }

    /** Utility: check if a tool is registered */
    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /** Utility: list registered tool names */
    public function names(): array
    {
        return array_keys($this->tools);
    }
}
